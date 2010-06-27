/*----------------------------------------------------------------------
  File    : attset.h
  Contents: attribute set management
  Author  : Christian Borgelt
  History : 1995.10.25 file created
            1995.11.03 functions as_write and as_read added
            1995.12.21 function att_valsort added
            1996.03.17 attribute types added
            1996.07.01 functions att_valmin and att_valmax added
            1996.07.04 attribute weights added
            1996.07.24 definitions of null values added
            1996.11.22 function as_chars added
            1997.02.25 function as_info added
            1997.03.12 attribute marks added
            1997.03.28 function as_weight added
            1997.08.01 restricted read/write/describe added
            1997.08.02 additional information output added
            1997.09.09 function as_scform added
            1997.09.26 error code added to as_err structure
            1998.01.04 separators made attribute set dependent
            1998.01.06 read/write functions made optional
            1998.01.10 variable null value characters added
            1998.02.08 function as_parse transferred from parse.h
            1998.03.18 function att_info added
            1998.06.22 deletion function moved to function as_create
            1998.06.23 major redesign, attribute functions introduced
            1998.08.16 lock functions removed, several functions added
            1998.08.19 typedef for attribute values (VAL) added
            1998.08.22 attribute set names and some functions added
            1998.08.30 parameters map and dir added to att_valsort
            1998.09.02 instance (current value) moved to attribute
            1998.09.06 second major redesign completed
            1998.09.12 deletion function parameter changed to ATT
            1998.09.14 attribute selection in as_write/as_desc improved
            1998.09.17 attribute selection improvements completed
            1998.09.24 parameter map added to function att_conv
            1988.09.25 function as_attaddm added
            1998.11.25 fucntions att_valcopy and as_attcopy added
            1998.11.29 functions att_clone and as_clone added
            1999.02.04 long int changed to int
            1999.04.17 definitions of AS_NOXATT and AS_NOXVAL added
            2000.11.22 function sc_form moved to module scan
            2001.05.13 definition of AS_NONULL added
            2001.07.14 global variable as_err replaced by a function
            2004.08.12 adapted to new module parse
            2007.02.13 adapted to redesigned module tabscan
            2007.02.17 attribute directions added
            2007.09.02 order of parameters to as_chars modified
----------------------------------------------------------------------*/
#ifndef __ATTSET__
#define __ATTSET__
#include <stdio.h>
#include <limits.h>
#include <float.h>
#ifdef AS_RDWR
#include "tabscan.h"
#endif
#ifdef AS_PARSE
#include "parse.h"
#else
#include "scan.h"
#endif

/*----------------------------------------------------------------------
  Preprocessor Definitions
----------------------------------------------------------------------*/
#define CCHAR      const char   /* abbreviation */
#define CINST      const INST   /* ditto */

/* --- attribute types --- */
#define AT_NOM     0x0001       /* nominal-valued */
#define AT_INT     0x0002       /* integer-valued */
#define AT_REAL    0x0004       /* real-valued */
#define AT_ALL     0x0007       /* all types (for function as_parse) */
#define AT_AUTO    (-1)         /* automatic type conversion */

/* --- null values --- */
#define NV_NOM     (-1)         /* null nominal value */
#define NV_INT     INT_MIN      /* null integer value */
#define NV_REAL    (-FLT_MAX)   /* null real    value */

/* --- attribute directions --- */
#define DIR_NONE   0            /* no direction */
#define DIR_IN     1            /* input  attribute */
#define DIR_OUT    2            /* output attribute */
#define DIR_ID     3            /* identifier */
#define DIR_WGT    4            /* weight (tuple occurences) */

/* --- cut/copy flags --- */
#define AS_ALL     0x0000       /* cut/copy all      atts./values */
#define AS_RANGE   0x0010       /* cut/copy range of atts./values */
#define AS_MARKED  0x0020       /* cut/copy marked   atts./values */
#define AS_SELECT  0x0040       /* cut/copy selected attributes */

/* --- read/write flags --- */
#define AS_INST    0x0000       /* read/write instances */
#define AS_ATT     0x0001       /* read/write attributes */
#define AS_DFLT    0x0002       /* create default attribute names */
#define AS_NOXATT  0x0004       /* do not extend set of attributes */
#define AS_NOXVAL  0x0008       /* do not extend set of values */
#define AS_NOEXT   (AS_NOXATT|AS_NOXVAL)   /* do not extend either */
#define AS_NONULL  0x0100       /* do not accept null values */
#define AS_RDORD   0x0200       /* write fields in read order */
#define AS_ALIGN   0x0400       /* align fields (pad with blanks) */
#define AS_ALNHDR  0x0800       /* align fields respecting a header */
#define AS_WEIGHT  0x1000       /* last field contains inst. weight */
#define AS_INFO1   0x2000       /* write add. info. 1 (before weight) */
#define AS_INFO2   0x4000       /* write add. info. 2 (after  weight) */
/* also applicable: AS_RANGE, AS_MARKED */

/* --- description flags --- */
#define AS_TITLE   0x0001       /* title with att. set name (comment) */
#define AS_IVALS   0x0002       /* intervals for numeric attributes */
#define AS_DIRS    0x0004       /* print attribute directions */
/* also applicable: AS_RANGE, AS_MARKED, AS_WEIGHT */

/* --- sizes --- */
#define AS_MAXLEN     255       /* maximal name length */

/* --- error codes --- */
#ifndef OK
#define OK              0       /* no error */
#define E_NONE          0       /* no error */
#define E_NOMEM       (-1)      /* not enough memory */
#define E_FOPEN       (-2)      /* file open failed */
#define E_FREAD       (-3)      /* file read failed */
#define E_FWRITE      (-4)      /* file write failed */
#endif
#ifndef E_VALUE
#define E_VALUE      (-16)      /* invalid field value */
#define E_FLDCNT     (-17)      /* wrong number of fields */
#define E_EMPFLD     (-18)      /* empty field name */
#define E_DUPFLD     (-19)      /* duplicate field name */
#define E_MISFLD     (-20)      /* missing field name */
#endif

/*----------------------------------------------------------------------
  Type Definitions
----------------------------------------------------------------------*/
typedef union {                 /* --- instance --- */
  int    i;                     /* identifier or integer number */
  float  f;                     /* floating point number */
  char   *s;                    /* pointer to string (unused) */
  void   *p;                    /* arbitrary pointer (unused) */
} INST;                         /* (instance) */

typedef struct _val {           /* --- attribute value --- */
  int          id;              /* identifier (index in attribute) */
  unsigned int hval;            /* hash value of value name */
  struct _val *succ;            /* successor in hash bucket */
  char         name[1];         /* value name */
} VAL;                          /* (attribute value) */

typedef int VAL_CMPFN (const char *name1, const char *name2);

typedef struct _att {           /* --- attribute --- */
  char   *name;                 /* attribute name */
  int    type;                  /* attribute type, e.g. AT_NOM */
  int    dir;                   /* direction,      e.g. DIR_IN */
  float  weight;                /* weight, e.g. to indicate relevance */
  int    mark;                  /* mark,   e.g. to indicate usage */
  int    read;                  /* read flag (used e.g. in as_read) */
  int    valvsz;                /* size of value vector */
  int    valcnt;                /* number of values in vector */
  VAL    **vals;                /* value vector (nominal attributes) */
  VAL    **htab;                /* hash table for values */
  INST   min, max;              /* minimal and maximal value/id */
  int    attwd[2];              /* attribute name widths */
  int    valwd[2];              /* maximum of value name widths */
  INST   inst;                  /* instance (current value) */
  INST   info;                  /* additional attribute information */
  struct _attset *set;          /* containing attribute set (if any) */
  int    id;                    /* identifier (index in att. set) */
  unsigned int hval;            /* hash value of attribute name */
  struct _att *succ;            /* successor in hash bucket */
} ATT;                          /* (attribute) */

typedef void ATT_DELFN (ATT *att);
typedef void ATT_APPFN (ATT *att, void *data);
typedef int  ATT_SELFN (const ATT *att, void *data);

typedef struct _attset {        /* --- attribute set --- */
  char    *name;                /* name of attribute set */
  int     attvsz;               /* size of attribute vector */
  int     attcnt;               /* number of attributes in vector */
  ATT     **atts;               /* attribute vector */
  ATT     **htab;               /* hash table for attributes */
  float   weight;               /* weight (of current instantiation) */
  INST    info;                 /* info. (for current instantiation) */
  ATT_DELFN *delfn;             /* attribute deletion function */
  #if defined AS_RDWR || defined AS_FLDS
  int     fldvsz;               /* size of field vector */
  int     fldcnt;               /* number of fields */
  int     *flds;                /* field vector for as_read() */
  #endif                        /* and as_write() (flag AS_RDORD) */
  #ifdef AS_RDWR
  char    chars[8];             /* special characters */
  TABSCAN *tscan;               /* table scanner */
  TSINFO  *err;                 /* error information */
  char    buf[4*AS_MAXLEN+4];   /* buffer for error information */
  #endif                        /* for function as_read() */
} ATTSET;                       /* (attribute set) */

typedef void AS_DELFN (ATTSET *set);
typedef void INFOUTFN (ATTSET *set, FILE *file, int mode, CCHAR *chars);

/*----------------------------------------------------------------------
  Attribute Functions
----------------------------------------------------------------------*/
extern ATT*     att_create  (const char *name, int type);
extern ATT*     att_clone   (const ATT *att);
extern void     att_delete  (ATT *att);
extern int      att_rename  (ATT *att, const char *name);
extern int      att_conv    (ATT *att, int type, INST *map);
extern int      att_cmp     (const ATT *att1, const ATT *att2);

extern CCHAR*   att_name    (const ATT *att);
extern int      att_type    (const ATT *att);
extern int      att_width   (const ATT *att, int scform);
extern int      att_setdir  (ATT *att, int dir);
extern int      att_getdir  (const ATT *att);
extern int      att_setmark (ATT *att, int mark);
extern int      att_getmark (const ATT *att);
extern float    att_setwgt  (ATT *att, float weight);
extern float    att_getwgt  (const ATT *att);
extern INST*    att_inst    (ATT *att);
extern INST*    att_info    (ATT *att);
extern ATTSET*  att_attset  (ATT *att);
extern int      att_id      (const ATT *att);

/*----------------------------------------------------------------------
  Attribute Value Functions
----------------------------------------------------------------------*/
extern int      att_valadd  (ATT *att, CCHAR *name, INST *inst);
extern void     att_valrem  (ATT *att, int valid);
extern void     att_valexg  (ATT *att, int valid1, int valid2);
extern void     att_valmove (ATT *att, int off, int cnt, int pos);
extern int      att_valcut  (ATT *dst, ATT *src, int mode, ...);
extern int      att_valcopy (ATT *dst, const ATT *src, int mode, ...);
extern void     att_valsort (ATT *att, VAL_CMPFN cmpfn,
                            int *map, int dir);

extern int      att_valid   (const ATT *att, const char *name);
extern CCHAR*   att_valname (const ATT *att, int valid);
extern int      att_valcnt  (const ATT *att);
extern int      att_valwd   (ATT *att, int scform);
extern CINST*   att_valmin  (const ATT *att);
extern CINST*   att_valmax  (const ATT *att);

/*----------------------------------------------------------------------
  Attribute Set Functions
----------------------------------------------------------------------*/
extern ATTSET*  as_create   (const char *name, ATT_DELFN delfn);
extern ATTSET*  as_clone    (const ATTSET *set);
extern void     as_delete   (ATTSET *set);
extern int      as_rename   (ATTSET *set, const char *name);
extern int      as_cmp      (const ATTSET *set1, const ATTSET *set2);

extern CCHAR*   as_name     (const ATTSET *set);
extern float    as_setwgt   (ATTSET *set, float weight);
extern float    as_getwgt   (const ATTSET *set);
extern INST*    as_info     (ATTSET *set);

extern int      as_attadd   (ATTSET *set, ATT *att);
extern int      as_attaddm  (ATTSET *set, ATT **att, int cnt);
extern ATT*     as_attrem   (ATTSET *set, int attid);
extern void     as_attexg   (ATTSET *set, int attid1, int attid2);
extern void     as_attmove  (ATTSET *set, int off, int cnt, int pos);
extern int      as_attcut   (ATTSET *dst, ATTSET *src, int mode, ...);
extern int      as_attcopy  (ATTSET *dst, const ATTSET *src,
                             int mode, ...);
extern int      as_attid    (const ATTSET *set, const char *name);
extern ATT*     as_att      (ATTSET *set, int attid);
extern int      as_attcnt   (const ATTSET *set);

extern void     as_apply    (ATTSET *set, ATT_APPFN appfn, void *data);
extern int      as_save     (const ATTSET *set, FILE *file);
extern int      as_load     (ATTSET *set, FILE *file);
#ifdef AS_RDWR
extern CCHAR*   as_chars    (ATTSET *set,    CCHAR *recseps,
                             CCHAR *fldseps, CCHAR *blanks,
                             CCHAR *nullchs, CCHAR *comment);
extern TABSCAN* as_tabscan  (ATTSET *set);
extern TSINFO*  as_err      (ATTSET *set);
extern int      as_read     (ATTSET *set, FILE *file, int mode, ...);
extern int      as_write    (ATTSET *set, FILE *file, int mode, ...);
#endif
extern int      as_desc     (ATTSET *set, FILE *file, int mode,
                             int maxlen, ...);
#ifdef AS_PARSE
extern int      as_parse    (ATTSET *set, SCAN *scan, int types);
#endif

#ifndef NDEBUG
extern void     as_stats    (const ATTSET *set);
#endif

/*----------------------------------------------------------------------
  Preprocessor Definitions
----------------------------------------------------------------------*/
#define att_name(a)         ((CCHAR*)(a)->name)
#define att_type(a)         ((a)->type)
#define att_width(a,s)      ((a)->attwd[(s) ? 1 : 0])
#define att_setdir(a,d)     ((a)->dir  = (d))
#define att_getdir(a)       ((a)->dir)
#define att_setmark(a,m)    ((a)->mark = (m))
#define att_getmark(a)      ((a)->mark)
#define att_setwgt(a,w)     ((a)->weight = (float)(w))
#define att_getwgt(a)       ((a)->weight)
#define att_inst(a)         (&(a)->inst)
#define att_info(a)         (&(a)->info)
#define att_attset(a)       ((a)->set)
#define att_id(a)           ((a)->id)

/*--------------------------------------------------------------------*/
#define att_valname(a,i)    ((CCHAR*)(a)->vals[i]->name)
#define att_valcnt(a)       ((a)->valcnt)
#define att_valmin(a)       ((CINST*)&(a)->min)
#define att_valmax(a)       ((CINST*)&(a)->max)

/*--------------------------------------------------------------------*/
#define as_name(s)          ((CCHAR*)(s)->name)
#define as_info(s)          (&(s)->info)
#define as_getwgt(s)        ((s)->weight)
#define as_setwgt(s,w)      ((s)->weight = (float)(w))

#define as_att(s,i)         ((s)->atts[i])
#define as_attcnt(s)        ((s)->attcnt)

#ifdef AS_RDWR
#define as_tabscan(s)       ((s)->tscan)
#define as_err(s)           ((s)->err)
#endif

#endif
