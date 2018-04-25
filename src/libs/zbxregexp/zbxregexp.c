/*
** Zabbix
** Copyright (C) 2001-2018 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"
#include "zbxregexp.h"

/* remove */
#include "log.h"

/******************************************************************************
 *                                                                            *
 * Function: zbx_regexp_compile                                               *
 *                                                                            *
 * Purpose: compiles a regular expression                                     *
 *                                                                            *
 * Parameters:                                                                *
 *     pattern   - [IN] regular expression as a text string. Empty            *
 *                      string ("") is allowed, it will match everything.     *
 *                      NULL is not allowed.                                  *
 *     flags     - [IN] regexp compilation parameters passed to pcre_compile. *
 *                      PCRE_CASELESS, PCRE_NO_AUTO_CAPTURE, PCRE_MULTILINE.  *
 *     error     - [OUT] error message if any.                                *
 *                                                                            *
 * Return value: compiled regexp or NULL                                      *
 *                                                                            *
 ******************************************************************************/
pcre	*zbx_regexp_compile(const char *pattern, int flags, const char **error)
{
	int error_offset = -1;
	pcre* regexp = pcre_compile(pattern, flags, error, &error_offset, 0);
	return regexp;
}

static pcre	*regexp_prepare(const char *pattern, int flags, const char **error)
{
	ZBX_THREAD_LOCAL static pcre	*old_regexp = NULL;
	ZBX_THREAD_LOCAL static char	*old_pattern = NULL;
	ZBX_THREAD_LOCAL static int	 old_flags;

	pcre *new_regexp = NULL;

	if (NULL == old_regexp || 0 != strcmp(old_pattern, pattern) || old_flags != flags)
	{
		if (NULL != old_regexp)
			zbx_regexp_free(old_regexp);
		old_regexp = NULL;
		old_regexp = NULL;
		old_pattern = NULL;
		old_flags = 0;

		new_regexp = zbx_regexp_compile(pattern, flags, error);

		if (new_regexp)
		{
			old_regexp = new_regexp;
			old_pattern = zbx_strdup(old_pattern, pattern);
			old_flags = flags;
		}
	}

	return old_regexp;
}

/****************************************************************************************************
 *                                                                                                  *
 * Function: zbx_regexp_exec                                                                        *
 *                                                                                                  *
 * Purpose: wrapper for pcre_exec(), searches for a given pattern, specified by                     *
 *          regexp, in the string                                                                   *
 *                                                                                                  *
 * Parameters:                                                                                      *
 *     string         - [IN] string to be matched against 'regexp'                                  *
 *     regexp         - [IN] precompiled regular expression                                         *
 *     flags          - [IN] execution flags for matching                                           *
 *     count          - [IN] count of elements in matches array                                     *
 *     matches        - [OUT] matches (can be NULL if matching results are                          *
 *                      not required)                                                               *
 *                                                                                                  *
 * Return value: ZBX_REGEXP_MATCH     - the string matches the specified regular expression         *
 *               ZBX_REGEXP_NO_MATCH  - the string does not match the specified regular expression  *
 *               ZBX_REGEXP_ERROR     - invalid regular expression                                  *
 *               ZBX_REGEXP_RUNAWAY   - catastrophic backtracking                                   *
 *                                                                                                  *
 ****************************************************************************************************/
int	zbx_regexp_exec(const char *string, const pcre *regexp, int flags, size_t count, zbx_regmatch_t *matches)
{
	int result = 0, r = 0;

#       define	MAX_REQUESTED_MATCHES 10
#       define	MATCHES_BUFF_SIZE (MAX_REQUESTED_MATCHES * 3)

	ZBX_THREAD_LOCAL static int matches_buff[MATCHES_BUFF_SIZE];
	int* ovector = NULL;
	int ovecsize = count*2 + count;

	if (MAX_REQUESTED_MATCHES < count)
		ovector = (int*)zbx_malloc(NULL, ovecsize * sizeof(int));
	else
		ovector = matches_buff;

	r = pcre_exec(regexp, NULL, string, strlen(string), flags, 0, ovector, ovecsize);

	if(0 <= r)
	{
		if(matches)
			memcpy(matches, ovector, count * sizeof(zbx_regmatch_t));
		result = ZBX_REGEXP_MATCH;
	}
	else if (PCRE_ERROR_NOMATCH == r)
	{
		result = ZBX_REGEXP_NO_MATCH;
	}
	else if (PCRE_ERROR_MATCHLIMIT == r)
	{
		result = ZBX_REGEXP_RUNAWAY;
	}
	else
	{	result = ZBX_REGEXP_ERROR;
	}

	if (MAX_REQUESTED_MATCHES < count)
		zbx_free(ovector);

	return result;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_regexp_free                                                  *
 *                                                                            *
 * Purpose: wrapper for pcre_free                                             *
 *                                                                            *
 * Parameters: regexp - [IN] compiled regular expression                      *
 *                                                                            *
 ******************************************************************************/
void	zbx_regexp_free(pcre *regexp)
{
	pcre_free(regexp);
}

/****************************************************************************************************
 *                                                                                                  *
 * Function: zbx_regexp_match_precompiled                                                           *
 *                                                                                                  *
 * Purpose: checks if string matches a precompiled regular expression without                       *
 *          returning matching groups                                                               *
 *                                                                                                  *
 * Parameters: string - [IN] string to be matched                                                   *
 *             regexp - [IN] precompiled regular expression                                         *
 *                                                                                                  *
 * Return value: ZBX_REGEXP_MATCH     - the string matches the specified regular expression         *
 *               ZBX_REGEXP_NO_MATCH  - the string does not match the specified regular expression  *
 *               ZBX_REGEXP_ERROR     - invalid regular expression                                  *
 *               ZBX_REGEXP_RUNAWAY   - catastrophic backtracking                                   *
 *                                                                                                  *
 * Comments: use this function for better performance if many strings need to                       *
 *           be matched against the same regular expression                                         *
 *                                                                                                  *
 ****************************************************************************************************/
int     zbx_regexp_match_precompiled(const char *string, const pcre *regexp)
{
	return zbx_regexp_exec(string, regexp, 0, (size_t)0, NULL);
}

static char	*zbx_regexp(const char *string, const char *pattern, int *len, int flags, int *result)
{
	char		*c = NULL;
	zbx_regmatch_t	match;
	pcre* 		regexp = NULL;
	const char*	error = NULL;
	int 		r = ZBX_REGEXP_ERROR;

	if (NULL != len)
		*len = FAIL;

	regexp = regexp_prepare(pattern, flags, &error);

	if (NULL != regexp && NULL != string)
	{
		r = zbx_regexp_exec(string, regexp, 0, 1, &match);
		if (ZBX_REGEXP_MATCH == r)
		{
			c = (char *)string + match.rm_so;

			if (NULL != len)
				*len = match.rm_eo - match.rm_so;
		}
		else if (NULL != len)
		{
			*len = SUCCEED;
		}
	}

	if(NULL != result)
		*result = r;

	return c;
}

char	*zbx_regexp_match(const char *string, const char *pattern, int *len)
{
	return zbx_regexp(string, pattern, len, PCRE_MULTILINE, NULL);
}

char	*zbx_iregexp_match(const char *string, const char *pattern, int *len)
{
	return zbx_regexp(string, pattern, len, PCRE_CASELESS | PCRE_MULTILINE, NULL);
}

/*********************************************************************************
 *                                                                               *
 * Function: regexp_sub_replace                                                  *
 *                                                                               *
 * Purpose: Constructs a string from the specified template and regexp match.    *
 *                                                                               *
 * Parameters: text            - [IN] the input string.                          *
 *             output_template - [IN] the output string template. The output     *
 *                                    string is constructed from template by     *
 *                                    replacing \<n> sequences with the captured *
 *                                    regexp group.                              *
 *                                    If the output template is NULL or contains *
 *                                    empty string then a copy of the whole      *
 *                                    input string is returned.                  *
 *             match           - [IN] the captured group data                    *
 *             nmatch          - [IN] the number of items in captured group data *
 *                                                                               *
 * Return value: Allocated string containing output value                        *
 *                                                                               *
 *********************************************************************************/
static char	*regexp_sub_replace(const char *text, const char *output_template, zbx_regmatch_t *match, size_t nmatch)
{
	char		*ptr = NULL;
	const char	*pstart = output_template, *pgroup;
	size_t		size = 0, offset = 0, group_index;

	if (NULL == output_template || '\0' == *output_template)
		return zbx_strdup(NULL, text);

	while (NULL != (pgroup = strchr(pstart, '\\')))
	{
		switch (*(++pgroup))
		{
			case '\\':
				zbx_strncpy_alloc(&ptr, &size, &offset, pstart, pgroup - pstart);
				pstart = pgroup + 1;
				continue;

			case '0':
			case '1':
			case '2':
			case '3':
			case '4':
			case '5':
			case '6':
			case '7':
			case '8':
			case '9':
				zbx_strncpy_alloc(&ptr, &size, &offset, pstart, pgroup - pstart - 1);
				group_index = *pgroup - '0';
				if (group_index < nmatch && -1 != match[group_index].rm_so)
				{
					zbx_strncpy_alloc(&ptr, &size, &offset, text + match[group_index].rm_so,
							match[group_index].rm_eo - match[group_index].rm_so);
				}
				pstart = pgroup + 1;
				continue;

			case '@':
				/* artificial construct to replace the first captured group or fail */
				/* if the regular expression pattern contains no groups             */
				if (-1 == match[1].rm_so)
				{
					zbx_free(ptr);
					goto out;
				}

				zbx_strncpy_alloc(&ptr, &size, &offset, text + match[1].rm_so,
						match[1].rm_eo - match[1].rm_so);

				pstart = pgroup + 1;
				continue;

			default:
				zbx_strncpy_alloc(&ptr, &size, &offset, pstart, pgroup - pstart);
				pstart = pgroup;
		}
	}

	if ('\0' != *pstart)
		zbx_strcpy_alloc(&ptr, &size, &offset, pstart);
out:
	return ptr;
}

/****************************************************************************************************
 *                                                                                                  *
 * Function: regexp_sub                                                                             *
 *                                                                                                  *
 * Purpose: Test if a string matches the specified regular expression. If yes                       *
 *          then create a return value by substituting '\<n>' sequences in                          *
 *          output template with the captured groups.                                               *
 *                                                                                                  *
 * Parameters: string          - [IN] the string to parse                                           *
 *             pattern         - [IN] the regular expression                                        *
 *             output_template - [IN] the output string template. The output                        *
 *                                    string is constructed from template by                        *
 *                                    replacing \<n> sequences with the captured                    *
 *                                    regexp group.                                                 *
 *                                    If output template is NULL or contains                        *
 *                                    empty string then the whole input string                      *
 *                                    is used as output value.                                      *
 *            flags            - [IN] the pcre_compile() function flags.                            *
 *                                    See pcre_compile() manual.                                    *
 *            out              - [OUT] the output value if the input string                         *
 *                                     matches the specified regular expression                     *
 *                                     or NULL otherwise                                            *
 * Return value: ZBX_REGEXP_MATCH     - the string matches the specified regular expression         *
 *               ZBX_REGEXP_NO_MATCH  - the string does not match the specified regular expression  *
 *               ZBX_REGEXP_ERROR     - invalid regular expression                                  *
 *               ZBX_REGEXP_RUNAWAY   - catastrophic backtracking                                   *
 *                                                                                                  *
 ****************************************************************************************************/
static int	regexp_sub(const char *string, const char *pattern, const char *output_template, int flags, char **out)
{
	int 		result = 0;
	const char* 	error = NULL;
	pcre		*regexp = NULL;

#	define MATCH_SIZE	 10
	zbx_regmatch_t		 match[MATCH_SIZE];

	if (NULL == string)
	{
		zbx_free(*out);
		return ZBX_REGEXP_MATCH;
	}

	/* no subpatterns without an output template */
	if (NULL == output_template || '\0' == *output_template)
		flags |= PCRE_NO_AUTO_CAPTURE;

	regexp = regexp_prepare(pattern, flags, &error);

	if (NULL != regexp)
	{
		result = zbx_regexp_exec(string, regexp, 0, MATCH_SIZE, match);
		if (0 <= result)
			*out = regexp_sub_replace(string, output_template, match, MATCH_SIZE);
	}

	return result;
}

/****************************************************************************************************
 *                                                                                                  *
 * Function: zbx_regexp_sub                                                                         *
 *                                                                                                  *
 * Purpose: Test if a string matches the specified regular expression. If yes                       *
 *          then create a return value by substituting '\<n>' sequences in                          *
 *          output template with the captured groups.                                               *
 *                                                                                                  *
 * Parameters: string          - [IN] the string to parse                                           *
 *             pattern         - [IN] the regular expression                                        *
 *             output_template - [IN] the output string template. The output                        *
 *                                    string is constructed from template by                        *
 *                                    replacing \<n> sequences with the captured                    *
 *                                    regexp group.                                                 *
 *             out             - [OUT] the output value if the input string                         *
 *                                     matches the specified regular expression                     *
 *                                     or NULL otherwise                                            *
 *                                                                                                  *
 * Return value: ZBX_REGEXP_MATCH     - the string matches the specified regular expression         *
 *               ZBX_REGEXP_NO_MATCH  - the string does not match the specified regular expression  *
 *               ZBX_REGEXP_ERROR     - invalid regular expression                                  *
 *               ZBX_REGEXP_RUNAWAY   - catastrophic backtracking                                   *
 *                                                                                                  *
 * Comments: This function performs case sensitive match                                            *
 *                                                                                                  *
 ****************************************************************************************************/
int	zbx_regexp_sub(const char *string, const char *pattern, const char *output_template, char **out)
{
	return regexp_sub(string, pattern, output_template, PCRE_MULTILINE, out);
}

/*********************************************************************************
 *                                                                               *
 * Function: zbx_mregexp_sub                                                     *
 *                                                                               *
 * Purpose: This function is similar to zbx_regexp_sub() with exception that     *
 *          multiline matches are accepted.                                      *
 *                                                                               *
 *********************************************************************************/
int	zbx_mregexp_sub(const char *string, const char *pattern, const char *output_template, char **out)
{
	return regexp_sub(string, pattern, output_template, 0, out);
}

/*********************************************************************************
 *                                                                               *
 * Function: zbx_iregexp_sub                                                     *
 *                                                                               *
 * Purpose: This function is similar to zbx_regexp_sub() with exception that     *
 *          case insensitive matches are accepted.                               *
 *                                                                               *
 *********************************************************************************/
int	zbx_iregexp_sub(const char *string, const char *pattern, const char *output_template, char **out)
{
	return regexp_sub(string, pattern, output_template, PCRE_CASELESS, out);
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_regexp_clean_expressions                                     *
 *                                                                            *
 * Purpose: frees expression data retrieved by DCget_expressions function or  *
 *          prepared with add_regexp_ex() function calls                      *
 *                                                                            *
 * Parameters: expressions  - [IN] a vector of expression data pointers       *
 *                                                                            *
 ******************************************************************************/
void	zbx_regexp_clean_expressions(zbx_vector_ptr_t *expressions)
{
	int	i;

	for (i = 0; i < expressions->values_num; i++)
	{
		zbx_expression_t	*regexp = expressions->values[i];

		zbx_free(regexp->name);
		zbx_free(regexp->expression);
		zbx_free(regexp);
	}

	zbx_vector_ptr_clear(expressions);
}

void	add_regexp_ex(zbx_vector_ptr_t *regexps, const char *name, const char *expression, int expression_type,
		char exp_delimiter, int case_sensitive)
{
	zbx_expression_t	*regexp;

	regexp = zbx_malloc(NULL, sizeof(zbx_expression_t));

	regexp->name = zbx_strdup(NULL, name);
	regexp->expression = zbx_strdup(NULL, expression);

	regexp->expression_type = expression_type;
	regexp->exp_delimiter = exp_delimiter;
	regexp->case_sensitive = case_sensitive;

	zbx_vector_ptr_append(regexps, regexp);
}

/**********************************************************************************
 *                                                                                *
 * Function: regexp_match_ex_regsub                                               *
 *                                                                                *
 * Purpose: Test if the string matches regular expression with the specified      *
 *          case sensitivity option and allocates output variable to store the    *
 *          result if necessary.                                                  *
 *                                                                                *
 * Parameters: string          - [IN] the string to check                         *
 *             pattern         - [IN] the regular expression                      *
 *             case_sensitive  - [IN] ZBX_IGNORE_CASE - case insensitive match.   *
 *                                    ZBX_CASE_SENSITIVE - case sensitive match.  *
 *             output_template - [IN] the output string template. The output      *
 *                                    string is constructed from the template by  *
 *                                    replacing \<n> sequences with the captured  *
 *                                    regexp group.                               *
 *                                    If output_template is NULL the whole        *
 *                                    matched string is returned.                 *
 *             output         - [OUT] a reference to the variable where allocated *
 *                                    memory containing the resulting value       *
 *                                    (substitution) is stored.                   *
 *                                    Specify NULL to skip output value creation. *
 *                                                                                *
 * Return value: ZBX_REGEXP_MATCH    - the string matches the specified regular   *
 *                                     expression                                 *
 *               ZBX_REGEXP_NO_MATCH - the string does not match the regular      *
 *                                     expression                                 *
 *               ZBX_REGEXP_RUNAWAY  - runaway expression                         *
 *               ZBX_REGEXP_ERROR    - the string is NULL or the specified        *
 *                                     regular expression is invalid              *
 *                                                                                *
 **********************************************************************************/
static int	regexp_match_ex_regsub(const char *string, const char *pattern, int case_sensitive,
		const char *output_template, char **output)
{
	int	regexp_flags = PCRE_MULTILINE, ret;

	if (ZBX_IGNORE_CASE == case_sensitive)
		regexp_flags |= PCRE_CASELESS;

	if (NULL == output)
	{
		zbx_regexp(string, pattern, NULL, regexp_flags, &ret);
	}
	else
	{
		ret = regexp_sub(string, pattern, output_template, regexp_flags, output);
	}

	return ret;
}

/**********************************************************************************
 *                                                                                *
 * Function: regexp_match_ex_substring                                            *
 *                                                                                *
 * Purpose: Test if the string contains substring with the specified case         *
 *          sensitivity option.                                                   *
 *                                                                                *
 * Parameters: string          - [IN] the string to check                         *
 *             pattern         - [IN] the substring to search                     *
 *             case_sensitive  - [IN] ZBX_IGNORE_CASE - case insensitive search   *
 *                                    ZBX_CASE_SENSITIVE - case sensitive search  *
 *                                                                                *
 * Return value: ZBX_REGEXP_MATCH    - string contains the specified substring    *
 *               ZBX_REGEXP_NO_MATCH - string does not contain the substring      *
 *                                                                                *
 **********************************************************************************/
static int	regexp_match_ex_substring(const char *string, const char *pattern, int case_sensitive)
{
	char	*ptr = NULL;

	switch (case_sensitive)
	{
		case ZBX_CASE_SENSITIVE:
			ptr = strstr(string, pattern);
			break;
		case ZBX_IGNORE_CASE:
			ptr = zbx_strcasestr(string, pattern);
			break;
	}

	return (NULL != ptr ? ZBX_REGEXP_MATCH : ZBX_REGEXP_NO_MATCH);
}

/**********************************************************************************
 *                                                                                *
 * Function: regexp_match_ex_substring_list                                       *
 *                                                                                *
 * Purpose: Test if the string contains a substring from list with the specified  *
 *          delimiter and case sensitivity option.                                *
 *                                                                                *
 * Parameters: string          - [IN] the string to check                         *
 *             pattern         - [IN] the substring list                          *
 *             case_sensitive  - [IN] ZBX_IGNORE_CASE - case insensitive search   *
 *                                    ZBX_CASE_SENSITIVE - case sensitive search  *
 *             delimiter       - [IN] the delimiter separating items in the       *
 *                                    substring list                              *
 *                                                                                *
 * Return value: ZBX_REGEXP_MATCH    - string contains a substring from the list  *
 *               ZBX_REGEXP_NO_MATCH - string does not contain any substrings     *
 *                                     from the list                              *
 *                                                                                *
 **********************************************************************************/
static int	regexp_match_ex_substring_list(const char *string, char *pattern, int case_sensitive, char delimiter)
{
	int	ret = ZBX_REGEXP_NO_MATCH;
	char	*s, *c;

	for (s = pattern; '\0' != *s && ZBX_REGEXP_MATCH != ret;)
	{
		if (NULL != (c = strchr(s, delimiter)))
			*c = '\0';

		ret = regexp_match_ex_substring(string, s, case_sensitive);

		if (NULL != c)
		{
			*c = delimiter;
			s = ++c;
		}
		else
			break;
	}

	return ret;
}

/**********************************************************************************
 *                                                                                *
 * Function: regexp_sub_ex                                                        *
 *                                                                                *
 * Purpose: Test if the string matches regular expression with the specified      *
 *          case sensitivity option and allocates output variable to store the    *
 *          result if necessary.                                                  *
 *                                                                                *
 * Parameters: regexps         - [IN] the global regular expression array         *
 *             string          - [IN] the string to check                         *
 *             pattern         - [IN] the regular expression or global regular    *
 *                                    expression name (@<global regexp name>).    *
 *             case_sensitive  - [IN] ZBX_IGNORE_CASE - case insensitive match    *
 *                                    ZBX_CASE_SENSITIVE - case sensitive match   *
 *             output_template - [IN] the output string template. For regular     *
 *                                    expressions (type Result is TRUE) output    *
 *                                    string is constructed from the template by  *
 *                                    replacing '\<n>' sequences with the         *
 *                                    captured regexp group.                      *
 *                                    If output_template is NULL then the whole   *
 *                                    matched string is returned.                 *
 *             output         - [OUT] a reference to the variable where allocated *
 *                                    memory containing the resulting value       *
 *                                    (substitution) is stored.                   *
 *                                    Specify NULL to skip output value creation. *
 *                                                                                *
 * Return value: ZBX_REGEXP_MATCH    - the string matches the specified regular   *
 *                                     expression                                 *
 *               ZBX_REGEXP_NO_MATCH - the string does not match the specified    *
 *                                     regular expression                         *
 *               FAIL                - invalid regular expression                 *
 *                                                                                *
 * Comments: For regular expressions and global regular expressions with 'Result  *
 *           is TRUE' type the 'output_template' substitution result is stored    *
 *           into 'output' variable. For other global regular expression types    *
 *           the whole string is stored into 'output' variable.                   *
 *                                                                                *
 **********************************************************************************/
int	regexp_sub_ex(const zbx_vector_ptr_t *regexps, const char *string, const char *pattern,
		int case_sensitive, const char *output_template, char **output)
{
	int	i, ret = FAIL;
	char	*output_accu;		/* accumulator for 'output' when looping over global regexp subexpressions */

	if (NULL == pattern || '\0' == *pattern)
	{
		/* always match when no pattern is specified */
		ret = ZBX_REGEXP_MATCH;
		goto out;
	}

	if ('@' != *pattern)				/* not a global regexp */
	{
		ret = regexp_match_ex_regsub(string, pattern, case_sensitive, output_template, output);
		goto out;
	}

	pattern++;
	output_accu = NULL;

	for (i = 0; i < regexps->values_num; i++)	/* loop over global regexp subexpressions */
	{
		const zbx_expression_t	*regexp = regexps->values[i];

		if (0 != strcmp(regexp->name, pattern))
			continue;

		switch (regexp->expression_type)
		{
			case EXPRESSION_TYPE_TRUE:
				if (NULL != output)
				{
					char	*output_tmp = NULL;

					if (ZBX_REGEXP_MATCH == (ret = regexp_match_ex_regsub(string,
							regexp->expression, regexp->case_sensitive, output_template,
							&output_tmp)))
					{
						zbx_free(output_accu);
						output_accu = output_tmp;
					}
				}
				else
				{
					ret = regexp_match_ex_regsub(string, regexp->expression, regexp->case_sensitive,
							NULL, NULL);
				}
				break;
			case EXPRESSION_TYPE_FALSE:
				ret = regexp_match_ex_regsub(string, regexp->expression, regexp->case_sensitive,
						NULL, NULL);
				if (FAIL != ret)	/* invert output value */
					ret = (ZBX_REGEXP_MATCH == ret ? ZBX_REGEXP_NO_MATCH : ZBX_REGEXP_MATCH);
				break;
			case EXPRESSION_TYPE_INCLUDED:
				ret = regexp_match_ex_substring(string, regexp->expression, regexp->case_sensitive);
				break;
			case EXPRESSION_TYPE_NOT_INCLUDED:
				ret = regexp_match_ex_substring(string, regexp->expression, regexp->case_sensitive);
				/* invert output value */
				ret = (ZBX_REGEXP_MATCH == ret ? ZBX_REGEXP_NO_MATCH : ZBX_REGEXP_MATCH);
				break;
			case EXPRESSION_TYPE_ANY_INCLUDED:
				ret = regexp_match_ex_substring_list(string, regexp->expression, regexp->case_sensitive,
						regexp->exp_delimiter);
				break;
			default:
				THIS_SHOULD_NEVER_HAPPEN;
				ret = FAIL;
		}

		if (FAIL == ret || ZBX_REGEXP_NO_MATCH == ret)
		{
			zbx_free(output_accu);
			break;
		}
	}

	if (ZBX_REGEXP_MATCH == ret && NULL != output_accu)
	{
		*output = output_accu;
		return ZBX_REGEXP_MATCH;
	}
out:
	if (ZBX_REGEXP_MATCH == ret && NULL != output && NULL == *output)
	{
		/* Handle output value allocation for global regular expression types   */
		/* that cannot perform output_template substitution (practically        */
		/* all global regular expression types except EXPRESSION_TYPE_TRUE).    */
		size_t	offset = 0, size = 0;

		zbx_strcpy_alloc(output, &size, &offset, string);
	}

	return ret;
}

int	regexp_match_ex(const zbx_vector_ptr_t *regexps, const char *string, const char *pattern, int case_sensitive)
{
	return regexp_sub_ex(regexps, string, pattern, case_sensitive, NULL, NULL);
}
