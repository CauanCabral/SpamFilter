/*----------------------------------------------------------------------
  File    : tab4vis.c
  Contents: table utility functions for visualization programs
  Author  : Christian Borgelt
  History : 2001.11.08 file created from file lvq.c
----------------------------------------------------------------------*/
#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include <time.h>
#include <assert.h>
#include "tab4vis.h"

/*----------------------------------------------------------------------
  Global Variables
----------------------------------------------------------------------*/
/* --- attribute set variables --- */
ATTSET      *attset   = NULL;   /* attribute set */
RANGE       *ranges   = NULL;   /* ranges of attribute values */
const char  **nms_met = NULL;   /* names of metric attributes */
int         *map_met  = NULL;   /* map metric attribs. to attset ids. */
int         metcnt    = 0;      /* number of metric attributes */
const char  **nms_nom = NULL;   /* names of nominal attributes */
int         *map_nom  = NULL;   /* map nom. attribs. to attset ids. */
int         nomcnt    = 0;      /* number of nominal attributes */
SELATT      selatt    =         /* attribute selection information */
  { 0, { 0.0, 1.0 }, 0, { 0.0, 1.0 }, 0 };

/* --- data table variables --- */
TABLE       *table    = NULL;   /* data table */
int         recno     = 0;      /* record number for error messages */
DATAFMT     datafmt   =         /* data format description */
  { 1, "\\n", " ,\\t", " \\t\\r", "?", "#" };
static FILE *file = NULL;       /* input file */

/*----------------------------------------------------------------------
  Functions
----------------------------------------------------------------------*/

void tv_clean (void)
{                               /* --- clean up the learning data */
  if (file)    { fclose(file);         file    = NULL; }
  if (table)   { tab_delete(table, 0); table   = NULL; }
  if (attset)  { as_delete(attset);    attset  = NULL; }
  if (nms_nom) { free((void*)nms_nom); nms_nom = NULL; }
  if (map_nom) { free(map_nom);        map_nom = NULL; }
  if (nms_met) { free((void*)nms_met); nms_met = NULL; }
  if (map_met) { free(map_met);        map_met = NULL; }
  if (ranges)  { free(ranges);         ranges  = NULL; }
  metcnt = nomcnt = 0;          /* clean up data variables */
}  /* tv_clean() */

/*--------------------------------------------------------------------*/

int tv_load (const char *fname, double addfrac)
{                               /* --- load a data table */
  int    i, k, n;               /* loop variables, buffers */
  ATT    *att;                  /* to traverse the attributes */
  RANGE  *rng;                  /* to traverse attribute value ranges */
  int    type;                  /* attribute type */
  float  f;                     /* buffer for a float value */
  double d;                     /* difference between max and min */

  assert(fname && (addfrac >= 0));  /* check the function arguments */
  tv_clean();                   /* delete existing table, maps etc. */

  /* --- create attribute set and load attributes --- */
  attset = as_create("domains", att_delete);
  if (!attset) return E_NOMEM;  /* create an attribute set */
  as_chars(attset, datafmt.recseps, datafmt.fldseps,
                   datafmt.blanks,  datafmt.nullchs, datafmt.comment);
  file = fopen(fname, "r");     /* open table file for input */
  if (!file) { tv_clean(); return E_FOPEN; }
  recno = 1;                    /* initialize the record number */
  k = ((datafmt.names) ? AS_ATT : AS_DFLT) | AS_MARKED;
  if (as_read(attset, file, k) != 0) { /* read the first record */
    i = as_err(attset)->code; tv_clean(); return i; }

  /* --- create table and load tuples --- */
  table = tab_create("table", attset, tpl_delete);
  if (!table) { tv_clean(); return E_NOMEM; }
                                /* create a new table */
  i = (k & AS_ATT) ? as_read(attset, file, AS_INST) : 0;
  if (k & AS_ATT) recno++;      /* read the first data record */
  while (i == 0) {              /* record read loop */
    if (tab_tpladd(table, NULL) != 0) {
      i = E_NOMEM; break; }     /* add read tuple to the table */
    recno++;                    /* count the processed record */
    i = as_read(attset, file, AS_INST);
  }                             /* read the next tuple */
  fclose(file); file = NULL;    /* close the input file */
  if (i < 0) { tv_clean(); return i; }

  /* --- determine attribute types --- */
  n = as_attcnt(attset);        /* get number of attributes and */
  for (k = n; --k >= 0; )       /* determine types automatically */
    tab_colconv(table, k, AT_AUTO);

  /* --- collect metric attributes --- */
  nms_met = (const char**)malloc(n *sizeof(const char*));
  map_met = (int*)        malloc(n *sizeof(int));
  ranges  = (RANGE*)      malloc(n *sizeof(RANGE));
  if (!nms_met || !map_met || !ranges) {
    tv_clean(); return E_NOMEM;}/* create name and map vectors */
  selatt.h_att = selatt.v_att = -1;
  rng = ranges;                 /* initialize attribute indices */
  for (k = metcnt = 0; k < n; k++) {
    att  = as_att(attset, k);   /* traverse the attributes */
    type = att_type(att);       /* and get the attribute type */
    if ((type != AT_INT) && (type != AT_REAL))
      continue;                 /* skip non-metric attributes */
    if      (selatt.h_att < 0)  /* note the first  metric attribute */
      selatt.h_att = metcnt;    /* for the horizontal direction */
    else if (selatt.v_att < 0)  /* note the second metric attribute */
      selatt.v_att = metcnt;    /* for the vertical direction */
    nms_met[metcnt]   = att_name(att);
    att_info(att)->i  = metcnt; /* note the attribute name */
    map_met[metcnt++] = k;      /* and the attribute identifier */
    if (type == AT_INT) {       /* if attribute is integer valued */
      i = att_valmin(att)->i;   /* get and set minimum value */
      rng->min = (i <=  INT_MIN +1) ? DBL_MAX : (double)i;
      i = att_valmax(att)->i;   /* get and set maximum value */
      rng->max = (i >=  INT_MAX)    ? DBL_MIN : (double)i; }
    else {                      /* if attribute is real valued */
      f = att_valmin(att)->f;   /* get and set minimum value */
      rng->min = (f <= -FLT_MAX)    ? DBL_MAX : (double)f;
      f = att_valmax(att)->f;   /* get and set maximum value */
      rng->max = (f >=  FLT_MAX)    ? DBL_MIN : (double)f;
    }                           /* (set initial range of values ) */
    d = rng->max -rng->min;     /* get difference between max and min */
    rng->min -= 0.5*addfrac *d; /* and adapt the range of values */
    rng->max += 0.5*addfrac *d; /* (add a frame around the points) */
    if      (rng->min >  rng->max) { rng->min  = 0; rng->max  = 1; }
    else if (rng->min == rng->max) { rng->min -= 1; rng->max += 1; }
    rng++;                      /* check and adapt final range */
  }                             /* and go to the next attribute */
  if (metcnt <= 0) { tv_clean(); return E_METCNT; }
  if (selatt.v_att < 0) selatt.v_att = selatt.h_att;
  selatt.h_rng = ranges[selatt.h_att];
  selatt.v_rng = ranges[selatt.v_att];

  /* --- collect nominal attributes --- */
  nms_nom = (const char**)malloc((n+1) *sizeof(const char*));
  map_nom = (int*)malloc(n *sizeof(int));
  if (!nms_nom || !map_nom) {   /* create name and map vectors */
    tv_clean(); return E_NOMEM; }
  for (k = nomcnt = 0; k < n; k++) {
    att  = as_att(attset, k);   /* traverse the attributes */
    type = att_type(att);       /* and get the attribute type */
    if ((type != AT_NOM) || (att_valcnt(att) <= 0))
      continue;                 /* skip non-nominal attributes */
    nms_nom[nomcnt]   = att_name(att);
    att_info(att)->i  = nomcnt; /* note the attribute name */
    map_nom[nomcnt++] = k;      /* and the attribute identifier */
  }
  nms_nom[nomcnt] = "<none>";   /* add an empty entry */
  selatt.c_att = nomcnt-1;      /* set the alleged class attribute */

  return 0;                     /* return `ok' */
}  /* tv_load() */
