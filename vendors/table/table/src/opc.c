/*----------------------------------------------------------------------
  File    : opc.c
  Contents: program to determine one point coverages
  Author  : Christian Borgelt
  History : 1997.02.26 file created
            1997.03.27 argument 'domfile' removed
            1997.03.28 adapted to changes of table functions
            1997.03.29 condensed one point coverages added
            1997.09.22 meaning of option -c changed to opposite
            1998.01.11 null value characters (option -u) added
            1998.02.27 header output made optional (option -w)
            1998.09.12 adapted to modified module attset
            1998.09.25 table reading simplified
            1998.09.27 hash table added for tuple lookup
            1998.09.28 hash table version debugged
            1999.02.07 input from stdin, output to stdout added
            1999.02.12 default header handling improved
            1999.03.17 adapted to table one point coverage functions
            1999.04.17 simplified using the new module 'io'
            1999.10.21 normalization of one point coverages added
            2001.07.14 adapted to modified module tabscan
            2003.08.16 slight changes in error message output
            2007.02.13 adapted to modified module attset
----------------------------------------------------------------------*/
#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>
#include <assert.h>
#include <math.h>
#ifndef AS_RDWR
#define AS_RDWR
#endif
#ifndef TAB_RDWR
#define TAB_RDWR
#endif
#include "io.h"
#ifdef STORAGE
#include "storage.h"
#endif

/*----------------------------------------------------------------------
  Preprocessor Definitions
----------------------------------------------------------------------*/
#define PRGNAME     "opc"
#define DESCRIPTION "determine one point coverages"
#define VERSION     "version 1.13 (2008.10.30)        " \
                    "(c) 1997-2008   Christian Borgelt"

/* --- sizes --- */
#define BLKSIZE     256         /* tuple vector block size */

/* --- error codes --- */
#define OK            0         /* no error */
#define E_NONE        0         /* no error */
#define E_NOMEM     (-1)        /* not enough memory */
#define E_FOPEN     (-2)        /* file open failed */
#define E_FREAD     (-3)        /* file read failed */
#define E_FWRITE    (-4)        /* file write failed */
#define E_OPTION    (-5)        /* unknown option */
#define E_OPTARG    (-6)        /* missing option argument */
#define E_ARGCNT    (-7)        /* wrong number of arguments */
#define E_UNKNOWN   (-8)        /* unknown error */

/*----------------------------------------------------------------------
  Constants
----------------------------------------------------------------------*/
static const char *errmsgs[] = {   /* error messages */
  /* E_NONE     0 */  "no error\n",
  /* E_NOMEM   -1 */  "not enough memory\n",
  /* E_FOPEN   -2 */  "cannot open file %s\n",
  /* E_FREAD   -3 */  "read error on file %s\n",
  /* E_FWRITE  -4 */  "write error on file %s\n",
  /* E_OPTION  -5 */  "unknown option -%c\n",
  /* E_OPTARG  -6 */  "missing option argument\n",
  /* E_ARGCNT  -7 */  "wrong number of arguments\n",
  /* E_UNKNOWN -8 */  "unknown error\n"
};

/*----------------------------------------------------------------------
  Global Variables
----------------------------------------------------------------------*/
const  char   *prgname = NULL;  /* program name for error messages */
static ATTSET *attset  = NULL;  /* attribute set */
static TABLE  *table   = NULL;  /* table */

/*----------------------------------------------------------------------
  Functions
----------------------------------------------------------------------*/

static void error (int code, ...)
{                               /* --- print an error message */
  va_list    args;              /* list of variable arguments */
  const char *msg;              /* error message */

  assert(prgname);              /* check the program name */
  if (code < E_UNKNOWN) code = E_UNKNOWN;
  if (code < 0) {               /* if to report an error, */
    msg = errmsgs[-code];       /* get the error message */
    if (!msg) msg = errmsgs[-E_UNKNOWN];
    fprintf(stderr, "\n%s: ", prgname);
    va_start(args, code);       /* get variable arguments */
    vfprintf(stderr, msg, args);/* print the error message */
    va_end(args);               /* end argument evaluation */
  }
  #ifndef NDEBUG                /* clean up memory */
  if (table)  tab_delete(table, 0);
  if (attset) as_delete(attset);
  #endif
  #ifdef STORAGE
  showmem("at end of program"); /* check memory usage */
  #endif
  exit(code);                   /* abort the program */
}  /* error() */

/*--------------------------------------------------------------------*/

int main (int argc, char *argv[])
{                               /* --- main function */
  int  i, k = 0;                /* loop variables, counter */
  char *s;                      /* to traverse options */
  char **optarg = NULL;         /* option argument */
  char *fn_hdr  = NULL;         /* name of table header file */
  char *fn_tab  = NULL;         /* name of table file */
  char *fn_opc  = NULL;         /* name of one point coverages file */
  char *blanks  = NULL;         /* blanks */
  char *fldseps = NULL;         /* field  separators */
  char *recseps = NULL;         /* record separators */
  char *nullchs = NULL;         /* null value characters */
  char *comment = NULL;         /* comment characters */
  int  inflags  = 0;                /* table file read  flags */
  int  outflags = AS_ATT|AS_WEIGHT; /* table file write flags */
  int  cond     = TAB_COND;     /* flag for condensed form */
  int  redonly  = 0;            /* flag for reduction only */

  prgname = argv[0];            /* get program name for error msgs. */

  /* --- print startup/usage message --- */
  if (argc > 1) {               /* if arguments are given */
    fprintf(stderr, "%s - %s\n", argv[0], DESCRIPTION);
    fprintf(stderr, VERSION); } /* print a startup message */
  else {                        /* if no argument is given */
    printf("usage: %s [options] "
                     "[-d|-h hdrfile] tabfile opcfile\n", argv[0]);
    printf("%s\n", DESCRIPTION);
    printf("%s\n", VERSION);
    printf("-p       do not compute one point coverages "
                    "(reduce only)\n");
    printf("-c       do not compute condensed form (expand fully)\n");
    printf("-z       normalize one point coverages\n");
    printf("-w       do not write field names to output file\n");
    printf("-a       align fields of output table "
                    "(default: do not align)\n");
    printf("-b#      blank   characters    (default: \" \\t\\r\")\n");
    printf("-f#      field   separators    (default: \" \\t\")\n");
    printf("-r#      record  separators    (default: \"\\n\")\n");
    printf("-C#      comment characters    (default: \"#\")\n");
    printf("-u#      null value characters (default: \"?*\")\n");
    printf("-n       number of tuple occurrences in last field\n");
    printf("-d       use default header "
                    "(field names = field numbers)\n");
    printf("-h       read table header (field names) from hdrfile\n");
    printf("hdrfile  file containing table header (field names)\n");
    printf("tabfile  table file to read "
                    "(field names in first record)\n");
    printf("opcfile  file to write one point coverages to\n");
    return 0;                   /* print usage message */
  }                             /* and abort program */

  /* --- evaluate arguments --- */
  for (i = 1; i < argc; i++) {  /* traverse arguments */
    s = argv[i];                /* get option argument */
    if (optarg) { *optarg = s; optarg = NULL; continue; }
    if ((*s == '-') && *++s) {  /* -- if argument is an option */
      while (1) {               /* traverse characters */
        switch (*s++) {         /* evaluate option */
          case 'p': redonly   = 1;                            break;
          case 'c': cond      = (cond & TAB_NORM) | TAB_FULL; break;
          case 'z': cond     |= TAB_NORM;                     break;
          case 'w': outflags &= ~AS_ATT;                      break;
          case 'a': outflags |= AS_ALIGN;                     break;
  	  case 'b': optarg    = &blanks;                      break;
          case 'f': optarg    = &fldseps;                     break;
          case 'r': optarg    = &recseps;                     break;
          case 'u': optarg    = &nullchs;                     break;
          case 'C': optarg    = &comment;                     break;
          case 'n': inflags  |= AS_WEIGHT;                    break;
          case 'd': inflags  |= AS_DFLT;                      break;
          case 'h': optarg    = &fn_hdr;                      break;
          default : error(E_OPTION, *--s);                    break;
        }                       /* set option variables */
        if (!*s) break;         /* if at end of string, abort loop */
        if (optarg) { *optarg = s; optarg = NULL; break; }
      } }                       /* get option argument */
    else {                      /* -- if argument is no option */
      switch (k++) {            /* evaluate non-option */
        case  0: fn_tab = s;      break;
        case  1: fn_opc = s;      break;
        default: error(E_ARGCNT); break;
      }                         /* note filenames */
    }
  }
  if (optarg) error(E_OPTARG);  /* check option argument */
  if (k != 2) error(E_ARGCNT);  /* check number of arguments */
  if (fn_hdr && (strcmp(fn_hdr, "-") == 0))
    fn_hdr = "";                /* convert "-" to "" */
  if (fn_hdr)                   /* set header flags */
    inflags = AS_ATT | (inflags & ~AS_DFLT);

  /* --- create attribute set and read table --- */
  attset = as_create("domains", att_delete);
  if (!attset) error(E_NOMEM);  /* create an attribute set */
  as_chars(attset, recseps, fldseps, blanks, nullchs, comment);
  fprintf(stderr, "\n");        /* set delimiter characters */
  table = io_tabin(attset, fn_hdr, fn_tab, inflags, "table", 1);
  if (!table) error(1);         /* read the table file */

  /* --- compute one point coverages --- */
  if (redonly) fprintf(stderr, "reducing table ... ");
  else         fprintf(stderr, "computing one point coverages ... ");
  tab_reduce(table);            /* reduce the table */
  if (!redonly && (tab_opc(table, cond) != 0))
    error(E_NOMEM);             /* determine one point coverages */
  fprintf(stderr, "done.\n");   /* and print a success message */

  /* --- write opc table --- */
  if (io_tabout(table, fn_opc, outflags, 1) != 0)
    error(1);                   /* write the one point coverages */

  /* --- clean up --- */
  #ifndef NDEBUG
  tab_delete(table, 1);         /* delete table and attribute set */
  #endif
  #ifdef STORAGE
  showmem("at end of program"); /* check memory usage */
  #endif
  return 0;                     /* return 'ok' */
}  /* main() */
