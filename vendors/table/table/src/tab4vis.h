/*----------------------------------------------------------------------
  File    : tab4vis.h
  Contents: table utility functions for visualization programs
  Author  : Christian Borgelt
  History : 2001.11.08 file created from file lvq.h
----------------------------------------------------------------------*/
#ifndef __TAB4VIS__
#define __TAB4VIS__
#ifndef AS_RDWR
#define AS_RDWR
#endif
#include "table.h"

/*----------------------------------------------------------------------
  Preprocessor Definitions
----------------------------------------------------------------------*/
#define FMTCHRLEN     80        /* length of format character strings */
#define E_METCNT     (-5)       /* no metric attribute */

/*----------------------------------------------------------------------
  Type Definitions
----------------------------------------------------------------------*/
typedef struct {                /* -- data format description -- */
  int    names;                 /* column names in first record */
  char   recseps[FMTCHRLEN+1];  /* record separators */
  char   fldseps[FMTCHRLEN+1];  /* field separators */
  char   blanks [FMTCHRLEN+1];  /* blank characters */
  char   nullchs[FMTCHRLEN+1];  /* null value characters */
  char   comment[FMTCHRLEN+1];  /* comment characters */
} DATAFMT;                      /* (data format description) */

typedef struct {                /* --- range of values --- */
  double min, max;              /* minimum and maximum value */
} RANGE;                        /* (range of values) */

typedef struct {                /* -- selected attributes -- */
  int    h_att;                 /* attribute for horizontal direction */
  RANGE  h_rng;                 /* horizontal range of values */
  int    v_att;                 /* attribute for vertical   direction */
  RANGE  v_rng;                 /* vertical range of values */
  int    c_att;                 /* class attribute (if any) */
} SELATT;                       /* (selected attributes) */

/*----------------------------------------------------------------------
  Global Variables
----------------------------------------------------------------------*/
/* --- attribute set variables --- */
extern ATTSET     *attset;      /* attribute set */
extern RANGE      *ranges;      /* ranges of attribute values */
extern const char **nms_met;    /* names of metric attributes */
extern int        *map_met;     /* map metric attribs. to attset ids. */
extern int        metcnt;       /* number of metric attributes */
extern const char **nms_nom;    /* names of nominal attributs */
extern int        *map_nom;     /* map nom. attribs. to attset ids. */
extern int        nomcnt;       /* number of nominal attributes */
extern SELATT     selatt;       /* attribute selection information */

/* --- data table variables --- */
extern TABLE      *table;       /* data table */
extern int        recno;        /* record number for error messages */
extern DATAFMT    datafmt;      /* data format description */

/*----------------------------------------------------------------------
  Functions
----------------------------------------------------------------------*/
extern void tv_clean (void);
extern int  tv_load  (const char *fname, double addfrac);

#endif
