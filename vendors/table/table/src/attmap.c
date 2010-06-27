/*----------------------------------------------------------------------
  File    : attmap.c
  Contents: attribute map management (for nominal to numeric coding)
  Author  : Christian Borgelt
  History : 2003.08.11 file created
            2003.08.12 function am_cnt added
            2004.08.10 bug concerning marked operation fixed
            2004.08.11 extended to target attribute handling
            2007.01.10 function am_target and mode AM_BIN2COL added
            2007.02.13 adapted to redesigned module attset
            2007.05.16 use of 1-in-n value corrected and extended
            2007.07.10 bug in am_exec fixed (binary attributes)
----------------------------------------------------------------------*/
#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include <assert.h>
#include "attmap.h"

/*----------------------------------------------------------------------
  Functions
----------------------------------------------------------------------*/

ATTMAP* am_create (ATTSET *attset, int mode, double one)
{                               /* --- create an attribute map */
  int    i, n, off;             /* loop variable, offset */
  int    attcnt = 0;            /* number of attributes */
  ATTMAP *map;                  /* created attribute map */
  ATT    *att;                  /* to traverse the attributes */
  AMEL   *p;                    /* to traverse the map elements */

  assert(attset);               /* check the function arguments */
  for (i = n = as_attcnt(attset); --i >= 0; ) {
    if (((mode & AM_MARKED) == 0)
    ||  (att_getmark(as_att(attset, i)) >= 0))
      attcnt++;                 /* count the attributes to map */
  }                             /* (needed for the memory allocation) */
  map = (ATTMAP*)malloc(sizeof(ATTMAP) +(attcnt-1) *sizeof(AMEL));
  if (!map) return NULL;        /* create an attribute map */
  map->attset = attset;         /* note the attribute set, */
  map->attcnt = attcnt;         /* the number of mapped attributes, */
  map->one    = one;            /* and the value for 1-in-n */
  for (p = map->amels, i = off = 0; i < n; i++) {
    att = as_att(attset, i);    /* traverse the attributes */
    if (((mode & AM_MARKED) != 0) && (att_getmark(att) < 0))
      continue;                 /* skip unmarked attributes */
    p->att = att;               /* note the attribute */
    p->off = off;               /* and  the current offset */
    switch (att_type(att)) {    /* evaluate the attribute type */
      case AT_REAL: p->type = -2; p->cnt = 1; break;
      case AT_INT : p->type = -1; p->cnt = 1; break;
      default     : p->type = att_valcnt(p->att);
                    p->cnt  = ((p->type >  2)
                           || ((p->type == 2) && (mode & AM_BIN2COL)))
                            ?   p->type : 1; break;
    }                           /* note the type/number of values */
    off += p->cnt; p++;         /* advance the offset by the number */
  }                             /* of dimensions mapped to */
  map->incnt  = off;            /* note the number of inputs */
  map->outcnt = 0;              /* and  the number of outputs */
  return map;                   /* return the created attribute map */
}  /* am_create() */

/*--------------------------------------------------------------------*/

void am_target (ATTMAP *map, int trgid)
{                               /* --- set a target attribute */
  int  i, k;                    /* loop variables */
  AMEL buf, *p;                 /* to traverse the map elements */

  assert(map && (trgid < map->attcnt)); /* check function arguments */
  if (map->outcnt > 0) {        /* if there is an old target */
    p   = map->amels +(i = map->attcnt -1);
    buf = *p;                   /* note the old target attribute */
    k   = att_id(buf.att);      /* and get its identifier */
    while ((--i >= 0) && (k < att_id((--p)->att))) {
      p[1] = p[0]; --p; }       /* shift up preceding attributes */
    p[0] = buf;                 /* store old target at proper place */
    map->outcnt = 0;            /* and clear the number of outputs */
  }
  if (trgid >= 0) {             /* if to set a new target attribute */
    for (p = map->amels +(i = map->attcnt); --i >= 0; )
      if (att_id((--p)->att) == trgid) break;
    if (i >= 0) {               /* find the new target attribute */
      buf = p[0];               /* note the new target attribute */
      while (++i < map->attcnt) {
        p[0] = p[1]; p++; }     /* traverse the successor attributes */
      p[0] = buf;               /* and shift them down to close gap */
      map->outcnt = buf.cnt;    /* store new target as the last att. */
    }                           /* and set the number of outputs */
  }
  p = map->amels;               /* traverse all map elements (again) */
  for (i = k = 0; i < map->attcnt; i++, p++) {
    p->off = k; k += p->cnt; }  /* update the mapping offsets */
  map->incnt = k -map->outcnt;  /* compute the number of inputs */
}  /* am_target() */

/*--------------------------------------------------------------------*/

void am_exec (ATTMAP *map, const TUPLE *tpl, int mode, double *vec)
{                               /* --- execute an attribute map */
  int        k, n, v;           /* loop variables, buffer */
  AMEL       *p;                /* to traverse the map elements */
  const INST *inst;             /* to traverse the instantiations */

  assert(map && vec);           /* check the function arguments */
  if (map->outcnt > 0) { k = map->attcnt -1; }
  else                 { k = map->attcnt; mode &= AM_INPUTS; }
  p = map->amels;               /* get the number of input attributes */
  if      (mode & AM_INPUTS) { if (mode & AM_TARGET) k++; }
  else if (mode & AM_TARGET) { p += k; k = 1; }
  else return;                  /* get the attribute range */
  for ( ; --k >= 0; p++) {      /* traverse the attributes */
    inst = (tpl) ? tpl_colval(tpl, att_id(p->att)) : att_inst(p->att);
    if (p->type < 0) {          /* map metric attributes directly */
      *vec++ = (p->type < -1) ? inst->f : inst->i; }
    else if (p->cnt < 2) {      /* if the attribute is binary, */
      v = inst->i;              /* set the value directly */
      *vec++ = (((v < 0) || (v > 1)) ? 0.5 : v) *fabs(map->one); }
    else {                      /* if the attribute is nominal */
      for (v = n = p->cnt; --v >= 0; )   
        vec[v] = 0;             /* clear all vector elements */
      v = inst->i;              /* get the nominal value and */
      if ((v >= 0) && (v < n))  /* set the corresponding element */
        vec[v] = (map->one < 0) ? -map->one /n : map->one;
      vec += n;                 /* skip the set elements */
    }                           /* of the output vector */
  }
}  /* am_exec() */
