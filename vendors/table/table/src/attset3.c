/*----------------------------------------------------------------------
  File    : attset3.c
  Contents: attribute set management, parser functions
  Author  : Christian Borgelt
  History : 1995.10.26 file created
            1998.03.10 attribute weights added to domain description
            1998.05.31 adapted to scanner changes
            1998.09.01 several assertions added
            1998.09.06 second major redesign completed
            1999.02.04 long int changed to int
            2000.11.22 functions sc_form and sc_len exported
            2001.06.23 module split into two files
            2001.07.15 parser adapted to modified module scan
            2002.01.22 parser functions moved to a separate file
            2002.06.18 full range no longer set for numeric attributes
            2004.08.12 error report for empty attribute set added
            2007.02.13 adapted to changed type identifiers
            2007.02.17 directions added, weight parsing modified
----------------------------------------------------------------------*/
#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>
#include <assert.h>
#ifndef AS_PARSE
#define AS_PARSE
#endif
#include "attset.h"
#ifdef STORAGE
#include "storage.h"
#endif

/*----------------------------------------------------------------------
  Parser Functions
----------------------------------------------------------------------*/

static int _domains (ATTSET *set, SCAN *scan, int tflags)
{                               /* --- parse attribute domains */
  ATT        *att;              /* attribute read */
  int        type;              /* attribute type */
  int        t;                 /* temporary buffer */
  double     wgt;               /* buffer for attribute weight */
  const char *v;                /* token value */

  while ((sc_token(scan) == T_ID) /* parse domain definitions */
  &&     ((strcmp(sc_value(scan), "dom")    == 0)
  ||      (strcmp(sc_value(scan), "domain") == 0))) {
    GET_TOK();                  /* consume 'dom' */
    GET_CHR('(');               /* consume '(' */
    t = sc_token(scan);         /* check next token for a valid name */
    if ((t != T_ID) && (t != T_NUM)) ERROR(E_ATTEXP);
    att = att_create(sc_value(scan), AT_NOM);
    if (!att) ERROR(E_NOMEM);   /* create an attribute and */
    t = as_attadd(set, att);    /* add it to the attribute set */
    if (t) { att_delete(att); ERROR((t > 0) ? E_DUPATT : E_NOMEM); }
    GET_TOK();                  /* consume attribute name */
    GET_CHR(')');               /* consume ')' */
    GET_CHR('=');               /* consume '=' */
    type = -1;                  /* init. attribute type to 'none' */
    t = sc_token(scan);         /* test next token */
    if      (t == '{')          /* if a set of values follows, */
      type = tflags & AT_NOM;   /* attribute is nominal */
    else if (t == T_ID) {       /* if an identifier follows */
      v = sc_value(scan);       /* get it for simpler comparisons */
      if      ((strcmp(v, "ZZ")      == 0)
      ||       (strcmp(v, "Z")       == 0)
      ||       (strcmp(v, "int")     == 0)
      ||       (strcmp(v, "integer") == 0))
        type = tflags & AT_INT; /* attribute is integer-valued */
      else if ((strcmp(v, "IR")      == 0)
      ||       (strcmp(v, "R")       == 0)
      ||       (strcmp(v, "real")    == 0)
      ||       (strcmp(v, "float")   == 0))
        type = tflags & AT_REAL;/* attribute is real-valued */
    }                           /* (get and check attribute type) */
    if (type <= 0) ERROR(E_DOMAIN);
    att->type = type;           /* set attribute type */
    if (type != AT_NOM) {       /* if attribute is numeric */
      GET_TOK();                /* consume type indicator */
      if (type == AT_INT) {     /* if attribute is integer-valued */
        att->min.i =  INT_MAX;  /* initialize minimal */
        att->max.i = -INT_MAX;} /* and maximal value */
      else {                    /* if attribute is real-valued */
        att->min.f =  FLT_MAX;  /* initialize minimal */
        att->max.f = -FLT_MAX;  /* and maximal value */
      }
      if (sc_token(scan) == '[') { /* if a range of values is given */
        GET_TOK();              /* consume '[' */
        if (sc_token(scan) != T_NUM) ERROR(E_NUMEXP);
        if (att_valadd(att, sc_value(scan), NULL) != 0)
          ERROR(E_NUMBER);      /* get and check lower bound */
        GET_TOK();              /* consume lower bound */
        GET_CHR(',');           /* consume ',' */
        if (sc_token(scan) != T_NUM) ERROR(E_NUMEXP);
        if (att_valadd(att, sc_value(scan), NULL) != 0)
          ERROR(E_NUMBER);      /* get and check upper bound */
        GET_TOK();              /* consume upper bound */
        GET_CHR(']');           /* consume ']' */
      } }
    else {                      /* if attribute is nominal */
      GET_CHR('{');             /* consume '{' */
      if (sc_token(scan) != '}') {
        while (1) {             /* read a list of values */
          t = sc_token(scan);   /* check for a name */
          if ((t != T_ID) && (t != T_NUM)) ERROR(E_VALEXP);
          t = att_valadd(att, sc_value(scan), NULL);
          if (t) ERROR((t > 0) ? E_DUPVAL : E_NOMEM);
          GET_TOK();            /* get and consume attribute value */
          if (sc_token(scan) != ',') break;
          GET_TOK();            /* if at end of list, abort loop, */
        }                       /* otherwise consume ',' */
      }
      GET_CHR('}');             /* consume '}' */
    }
    if (sc_token(scan) == ':'){ /* if a direction indication follows */
      GET_TOK();                /* consume ',' */
      if (sc_token(scan) != T_ID) ERR_STR("in");
      v = sc_value(scan);       /* get the direction indicator */
      if      (strcmp(v, "none") == 0) att->dir = DIR_NONE;
      else if (strcmp(v, "in")   == 0) att->dir = DIR_IN;
      else if (strcmp(v, "out")  == 0) att->dir = DIR_OUT;
      else if (strcmp(v, "id")   == 0) att->dir = DIR_ID;
      else if (strcmp(v, "wgt")  == 0) att->dir = DIR_WGT;
      else ERR_STR("in");       /* get the direction code */
      GET_TOK();                /* and consume the token */
    }
    if (sc_token(scan) == ','){ /* if a weight indication follows */
      if (sc_token(scan) != T_NUM)             ERROR(E_NUMEXP);
      wgt = atof(sc_value(scan));     /* get the attribute weight */
      if ((wgt <= NV_REAL) || (wgt > FLT_MAX)) ERROR(E_NUMBER);
      att->weight = (float)wgt; /* check and set attribute weight */
      GET_TOK();                /* and consume the token */
    }
    GET_CHR(';');               /* consume ';' */
  }  /* while ((sc_token(scan) == T_ID) .. */
  return 0;                     /* return 'ok' */
}  /* _domains() */

/*--------------------------------------------------------------------*/

int as_parse (ATTSET *set, SCAN *scan, int types)
{                               /* --- parse att. set description */
  int r, err = 0;               /* result of function, error flag */

  assert(set);                  /* check the function argument */
  pa_init(scan);                /* initialize parsing */
  if (types & AT_ALL) {         /* if at least one type flag is set */
    while (1) {                 /* read loop (with recovery) */
      r = _domains(set, scan, types);   /* read att. domains */
      if (r == 0) break;        /* if no error occurred, abort */
      err = r;                  /* otherwise set the error flag */
      if (r == E_NOMEM) break;  /* always abort on 'out of memory' */
      sc_recover(scan, ';', 0, 0, 0);
    }                           /* otherwise recover from the error */
    if (err) return -1;         /* read domain definitions */
  }                             /* and check for an error */
  if (set->attcnt <= 0) ERR_STR("dom");
  return err;                   /* return error flag */
}  /* as_parse() */
