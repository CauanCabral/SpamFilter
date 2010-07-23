/*----------------------------------------------------------------------
  File    : bcx.c
  Contents: naive and full Bayes classifier execution
  Author  : Christian Borgelt
  History : 1998.12.08 file created from file dtc.c
            1999.02.13 input from stdin, output to stdout added
            1999.04.17 simplified using the new module 'io'
            2000.11.20 option -m (max. likelihood est. for var.) added
            2000.11.21 adapted to redesigned module nbayes
            2000.11.30 full Bayes classifier execution added
            2001.07.16 adapted to modified module scan
            2002.09.18 bug concerning missing target fixed
            2003.02.02 bug in alignment in connection with -d fixed
            2003.04.23 missing AS_MARKED added for second reading
            2003.08.16 slight changes in error message output
            2005.02.22 classification threshold added (option -t)
            2006.01.17 format specification for confidence added
            2007.02.13 adapted to modified module attset
----------------------------------------------------------------------*/
#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>
#include <assert.h>
#ifndef SC_SCAN
#define SC_SCAN
#endif
#include "scan.h"
#ifndef AS_RDWR
#define AS_RDWR
#endif
#ifndef AS_PARSE
#define AS_PARSE
#endif
#include "io.h"
#ifndef NBC_PARSE
#define NBC_PARSE
#endif
#include "nbayes.h"
#ifndef FBC_PARSE
#define FBC_PARSE
#endif
#include "fbayes.h"
#ifdef STORAGE
#include "storage.h"
#endif

/*----------------------------------------------------------------------
  Preprocessor Definitions
----------------------------------------------------------------------*/
#define PRGNAME     "bcx"
#define DESCRIPTION "naive and full Bayes classifier execution"
#define VERSION     "version 2.17 (2008.10.30)        " \
                    "(c) 1998-2008   Christian Borgelt"

/* --- error codes --- */
#define OK            0         /* no error */
#define E_NONE        0         /* no error */
#define E_NOMEM     (-1)        /* not enough memory */
#define E_FOPEN     (-2)        /* cannot open file */
#define E_FREAD     (-3)        /* read error on file */
#define E_FWRITE    (-4)        /* write error on file */
#define E_OPTION    (-5)        /* unknown option */
#define E_OPTARG    (-6)        /* missing option argument */
#define E_ARGCNT    (-7)        /* wrong number of arguments */
#define E_STDIN     (-8)        /* double assignment of stdin */
#define E_PARSE     (-9)        /* parse error */
#define E_CLASS    (-10)        /* missing class */
#define E_NEGLC    (-11)        /* negative Laplace correction */
#define E_UNKNOWN  (-12)        /* unknown error */

/*----------------------------------------------------------------------
  Type Definitions
----------------------------------------------------------------------*/
typedef struct {                /* --- classification result info.--- */
  ATT    *att;                  /* class attribute */
  char   *n_class;              /* name  of classification column */ 
  int    w_class;               /* width of classification column */
  int    class;                 /* class (classification result) */
  char   *n_prob;               /* name  of probability column */
  int    w_prob;                /* width of probability column */
  double prob;                  /* probability of result */
  char   *format;               /* number output format */
  int    all;                   /* whether to show all probabilities */
} RESULT;                       /* (classification result info.) */

/*----------------------------------------------------------------------
  Constants
----------------------------------------------------------------------*/
static const char *errmsgs[] = {   /* error messages */
  /* E_NONE      0 */  "no error\n",
  /* E_NOMEM    -1 */  "not enough memory\n",
  /* E_FOPEN    -2 */  "cannot open file %s\n",
  /* E_FREAD    -3 */  "read error on file %s\n",
  /* E_FWRITE   -4 */  "write error on file %s\n",
  /* E_OPTION   -5 */  "unknown option -%c\n",
  /* E_OPTARG   -6 */  "missing option argument\n",
  /* E_ARGCNT   -7 */  "wrong number of arguments\n",
  /* E_STDIN    -8 */  "double assignment of standard input\n",
  /* E_PARSE    -9 */  "parse error(s) on file %s\n",
  /* E_CLASS   -10 */  "missing class \"%s\" in file %s\n",
  /* E_NEGLC   -11 */  "Laplace correction must not be negative\n",
  /* E_UNKNOWN -12 */  "unknown error\n"
};

/*----------------------------------------------------------------------
  Global Variables
----------------------------------------------------------------------*/
const  char   *prgname = NULL;  /* program name for error messages */
static SCAN   *scan    = NULL;  /* scanner */
static NBC    *nbc     = NULL;  /* naive Bayes classifier */
static FBC    *fbc     = NULL;  /* full  Bayes classifier */
static ATTSET *attset  = NULL;  /* attribute set */
static FILE   *in      = NULL;  /* input  file */
static FILE   *out     = NULL;  /* output file */
static RESULT res = {           /* classification result information */
  NULL,                         /* class attribute */
  "bc", 0, 0,                   /* data for classification column */
  NULL, 0, 0.0, "%.3f", 0 };    /* data for probability    column */

/*----------------------------------------------------------------------
  Functions
----------------------------------------------------------------------*/

static void error (int code, ...)
{                               /* --- print error message */
  va_list    args;              /* list of variable arguments */
  const char *msg;              /* error message */

  assert(prgname);              /* check the program name */
  if (code < E_UNKNOWN) code = E_UNKNOWN;
  if (code < 0) {               /* if to report an error, */
    msg = errmsgs[-code];       /* get error message */
    if (!msg) msg = errmsgs[-E_UNKNOWN];
    fprintf(stderr, "\n%s: ", prgname);
    va_start(args, code);       /* get variable arguments */
    vfprintf(stderr, msg, args);/* print error message */
    va_end(args);               /* end argument evaluation */
  }
  #ifndef NDEBUG
  if (nbc)    nbc_delete(nbc, 0);
  if (fbc)    fbc_delete(fbc, 0);
  if (attset) as_delete(attset);   /* clean up memory */
  if (scan)   sc_delete(scan);     /* and close files */
  if (in  && (in  != stdin))  fclose(in);
  if (out && (out != stdout)) fclose(out);
  #endif
  #ifdef STORAGE
  showmem("at end of program"); /* check memory usage */
  #endif
  exit(code);                   /* abort programm */
}  /* error() */

/*--------------------------------------------------------------------*/

static void infout (ATTSET *set, FILE *file, int mode, CCHAR *seps)
{                               /* --- write additional information */
  int    i, k;                  /* loop variables, buffers */
  CCHAR  *class;                /* class attribute/value name */
  char   *prob;                 /* name of probability column */
  double p;                     /* buffer for probability */
  char   buf[32];               /* buffer for output */

  if (mode & AS_ATT) {          /* if to write header */
    class = res.n_class;        /* get name of classification column */
    prob  = res.n_prob;         /* and name of probability column */
    if (mode & AS_ALIGN) {      /* if to align fields */
      if ((mode & AS_WEIGHT) || prob) {
        i = att_valwd(res.att, 0);
        k = (int)strlen(class); res.w_class = (i > k) ? i : k;
      }                         /* compute width of class column */
      if (prob && (mode & AS_WEIGHT)) {
        k = (int)strlen(prob);  res.w_prob  = (4 > k) ? 4 : k; }
    } }                         /* compute width of prob. column */
  else {                        /* if to write a normal record */
    class = att_valname(res.att, res.class);
    if (res.n_prob) sprintf(buf, res.format, res.prob);
    prob = buf;                 /* format the probability */
  }                             /* get and format field contents */
  k = fprintf(file, class);     /* write classification result */
  for (i = res.w_class -k; --i >= 0; )
    fputc(seps[0], file);       /* if to align, pad with blanks */
  if (res.n_prob) {             /* if to write class probability */
    fputc(seps[1], file);       /* write field separator */
    fputs(prob, file);          /* and number of errors */
    for (i = res.w_prob -k; --i >= 0; ) fputc(seps[0], file);
  }                             /* if to align, pad with blanks */
  if (!res.all) return;         /* if not to show probs., abort */
  k = att_valcnt(res.att);      /* get the number of values */
  if (mode & AS_ATT) {          /* if to write the header */
    for (i = 0; i < k; i++) {   /* traverse the target values */
      fputc(seps[1], file);     /* print a separator */
      fputs(att_valname(res.att, i), file);
    } }                         /* print the value name */
  else {                        /* if to write the activations */
    for (i = 0; i < k; i++) {   /* traverse the values */
      fputc(seps[1], file);     /* print a separator */
      p = (fbc) ? fbc_post(fbc, i) : nbc_post(nbc, i);
      fprintf(file, res.format, p);
    }                           /* print the probability */
  }                             /* (extended confidence information) */
}  /* infout() */

/*--------------------------------------------------------------------*/

int main (int argc, char* argv[])
{                               /* --- main function */
  int    i, k = 0, f;           /* loop variables, buffer */
  char   *s;                    /* to traverse options */
  char   **optarg = NULL;       /* option argument */
  char   *fn_hdr  = NULL;       /* name of table header file */
  char   *fn_tab  = NULL;       /* name of table file */
  char   *fn_bc   = NULL;       /* name of classifier file */
  char   *fn_out  = NULL;       /* name of output file */
  char   *blanks  = NULL;       /* blanks */
  char   *fldseps = NULL;       /* field  separators */
  char   *recseps = NULL;       /* record separators */
  char   *nullchs = NULL;       /* null value characters */
  char   *comment = NULL;       /* comment characters */
  double lcorr    = -DBL_MAX;   /* Laplace correction value */
  double thresh   = 0.5;        /* classification threshold */
  int    dwnull   = 0;          /* distribute weight of null values */
  int    maxllh   = 0;          /* max. likelihood est. of variance */
  int    inflags  = 0;          /* table file read  flags */
  int    outflags = AS_ATT;     /* table file write flags */
  int    tplcnt   = 0;          /* number of tuples */
  double tplwgt   = 0;          /* weight of tuples */
  double errcnt   = 0;          /* number of misclassifications */
  int    clscnt;                /* number of classes */
  int    attid;                 /* loop variable for attributes */
  float  wgt;                   /* tuple/instantiation weight */
  int    mode;                  /* classifier setup mode */
  TSINFO *err;                  /* error information */

  prgname = argv[0];            /* get program name for error msgs. */

  /* --- print startup/usage message --- */
  if (argc > 1) {               /* if arguments are given */
    fprintf(stderr, "%s - %s\n", argv[0], DESCRIPTION);
    fprintf(stderr, VERSION); } /* print a startup message */
  else {                        /* if no argument given */
    printf("usage: %s [options] bcfile "
                     "[-d|-h hdrfile] tabfile [outfile]\n", argv[0]);
    printf("%s\n", DESCRIPTION);
    printf("%s\n", VERSION);
    printf("-c#      classification field name "
                    "(default: \"%s\")\n", res.n_class);
    printf("-p#      confidence/probability field name "
                    "(default: no confidence output)\n");
    printf("-o#      output format for confidence/probability "
                    "(default: \"%s\")\n", res.format);
    printf("-x       print extended confidence information\n");
    printf("-L#      Laplace correction "
                    "(default: as specified in classifier)\n");
    printf("-t#      probability threshold "
                    "(two class problems only, default: %g)\n", thresh);
    printf("-v/V     (do not) distribute tuple weight "
                    "for null values\n");
    printf("-m/M     (do not) use maximum likelihood estimate "
                    "for the variance\n");
    printf("-a       align fields (default: do not align)\n");
    printf("-w       do not write field names to the output file\n");
    printf("-b#      blank   characters    (default: \" \\t\\r\")\n");
    printf("-f#      field   separators    (default: \" \\t\")\n");
    printf("-r#      record  separators    (default: \"\\n\")\n");
    printf("-C#      comment characters    (default: \"#\")\n");
    printf("-u#      null value characters (default: \"?*\")\n");
    printf("-n       number of tuple occurrences in last field\n");
    printf("bcfile   file containing classifier description\n");
    printf("-d       use default table header "
                    "(field names = field numbers)\n");
    printf("-h       read table header (field names) from hdrfile\n");
    printf("hdrfile  file containing table header (field names)\n");
    printf("tabfile  table file to read "
                    "(field names in first record)\n");
    printf("outfile  file to write output table to (optional)\n");
    return 0;                   /* print a usage message */
  }                             /* and abort the program */

  /* --- evaluate arguments --- */
  for (i = 1; i < argc; i++) {  /* traverse arguments */
    s = argv[i];                /* get option argument */
    if (optarg) { *optarg = s; optarg = NULL; continue; }
    if ((*s == '-') && *++s) {  /* -- if argument is an option */
      while (*s) {              /* traverse options */
        switch (*s++) {         /* evaluate option */
          case 'c': optarg    = &res.n_class;       break;
          case 'p': optarg    = &res.n_prob;        break;
          case 'o': optarg    = &res.format;        break;
          case 'x': res.all   = 1;                  break;
          case 'L': lcorr     = strtod(s, &s);      break;
          case 't': thresh    = strtod(s, &s);      break;
          case 'v': dwnull    = NBC_ALL;            break;
          case 'V': dwnull   |= NBC_DWNULL|NBC_ALL; break;
          case 'm': maxllh    = NBC_ALL;            break;
          case 'M': maxllh   |= NBC_MAXLLH|NBC_ALL; break;
          case 'n': inflags  |= AS_WEIGHT;
                    outflags |= AS_WEIGHT;          break;
          case 'a': outflags |= AS_ALIGN;           break;
          case 'w': outflags &= ~AS_ATT;            break;
          case 'b': optarg    = &blanks;            break;
          case 'f': optarg    = &fldseps;           break;
          case 'r': optarg    = &recseps;           break;
          case 'u': optarg    = &nullchs;           break;
          case 'C': optarg    = &comment;           break;
          case 'd': inflags  |= AS_DFLT;            break;
          case 'h': optarg    = &fn_hdr;            break;
          default : error(E_OPTION, *--s);          break;
        }                       /* set option variables */
        if (!*s) break;         /* if at end of string, abort loop */
        if (optarg) { *optarg = s; optarg = NULL; break; }
      } }                       /* get option argument */
    else {                      /* if argument is no option */
      switch (k++) {            /* evaluate non-option */
        case  0: fn_bc  = s;      break;
        case  1: fn_tab = s;      break;
        case  2: fn_out = s;      break;
        default: error(E_ARGCNT); break;
      }                         /* note filenames */
    }
  }
  if (optarg) error(E_OPTARG);  /* check the option argument */
  if ((k < 2) || (k > 3))       /* and the number of arguments */
    error(E_ARGCNT);
  if (fn_hdr && (strcmp(fn_hdr, "-") == 0))
    fn_hdr = "";                /* convert "-" to "" */
  i = (!fn_bc  || !*fn_bc) ? 1 : 0;
  if  (!fn_tab || !*fn_tab) i++;
  if  ( fn_hdr && !*fn_hdr) i++;/* check assignments of stdin: */
  if (i > 1) error(E_STDIN);    /* stdin must not be used twice */
  if ((lcorr < 0) && (lcorr > -DBL_MAX))
    error(E_NEGLC);             /* check the Laplace correction */
  if (fn_hdr)                   /* set the header file flag */
    inflags = AS_ATT | (inflags & ~AS_DFLT);
  if ((outflags & AS_ATT) && (outflags & AS_ALIGN))
    outflags |= AS_ALNHDR;      /* set align to header flag */

  /* --- read Bayes classifier --- */
  scan = sc_create(fn_bc);      /* create a scanner */
  if (!scan) error((!fn_bc || !*fn_bc) ? E_NOMEM : E_FOPEN, fn_bc);
  attset = as_create("domains", att_delete);
  if (!attset) error(E_NOMEM);  /* create an attribute set */
  fprintf(stderr, "\nreading %s ... ", sc_fname(scan));
  if ((sc_nexter(scan)   <  0)  /* start scanning (get first token) */
  ||  (as_parse(attset, scan, AT_ALL) != 0)
  ||  (as_attcnt(attset) <= 0)) /* parse attribute set */
    error(E_PARSE, sc_fname(scan));
  if ((sc_token(scan) == T_ID)  /* determine classifier type */
  &&  (strcmp(sc_value(scan), "fbc") == 0))
       fbc = fbc_parse(attset, scan);
  else nbc = nbc_parse(attset, scan);
  if ((!fbc && !nbc)            /* parse the Bayes classifier */
  ||   !sc_eof(scan))           /* and check for end of file */
    error(E_PARSE, sc_fname(scan));
  sc_delete(scan); scan = NULL; /* delete the scanner */
  fprintf(stderr, "[%d attribute(s)] done.\n", as_attcnt(attset));
  if ((lcorr >= 0) || dwnull || maxllh) {
    if (lcorr < 0)              /* get the classifier's parameters */
      lcorr = (fbc) ? fbc_lcorr(fbc) : nbc_lcorr(nbc);
    mode    = (fbc) ? fbc_mode(fbc)  : nbc_mode(nbc);
    if (dwnull) mode = (mode & ~NBC_DWNULL) | dwnull;
    if (maxllh) mode = (mode & ~NBC_MAXLLH) | maxllh;
                                /* adapt the estimation parameters */
    if (fbc) fbc_setup(fbc, mode, lcorr);
    else     nbc_setup(nbc, mode, lcorr);
  }                             /* set up the classifier anew */
  if (fbc) {                    /* if full Bayes classifier */
    clscnt  = fbc_clscnt(fbc);  /* get class information */
    res.att = as_att(attset, fbc_clsid(fbc)); }
  else {                        /* if naive Bayes classifier */
    clscnt  = nbc_clscnt(nbc);  /* get class information */
    res.att = as_att(attset, nbc_clsid(nbc));
  }                             /* (class att. and num. of classes) */

  /* --- read table header --- */
  for (attid = as_attcnt(attset); --attid >= 0; )
    att_setmark(as_att(attset, attid), 1);
  att_setmark(res.att, 0);      /* mark all attribs. except the class */
  as_chars(attset, recseps, fldseps, blanks, nullchs, comment);
  in = io_hdr(attset, fn_hdr, fn_tab, inflags|AS_MARKED, 1);
  if (!in) error(1);            /* read the table header */

  /* --- classify tuples --- */
  if ((att_getmark(res.att) < 0)/* either the class must be present */
  &&  (k <= 2))                 /* or an output file must be written */
    error(E_CLASS, att_name(res.att), fn_tab);
  if (k > 2) {                  /* if to write an output table */
    if ((outflags & AS_ALIGN)   /* if to align output file */
    &&  (in != stdin)) {        /* and not to read from stdin */
      i = AS_INST | (inflags & ~(AS_ATT|AS_DFLT));
      while (as_read(attset, in, i) == 0);
      fclose(in);               /* determine the column widths */
      fprintf(stderr, "done.\n");
      in = io_hdr(attset, fn_hdr, fn_tab, inflags|AS_MARKED, 1);
      if (!in) error(1);        /* reread the table header */
    }                           /* (necessary because of first tuple) */
    if (fn_out && *fn_out)      /* if a proper file name is given, */
      out = fopen(fn_out, "w"); /* open output file for writing */
    else {                      /* if no proper file name is given, */
      out = stdout; fn_out = "<stdout>"; }       /* write to stdout */
    if (!out) error(E_FOPEN, fn_out);
    k = AS_MARKED|AS_INFO1|AS_RDORD|outflags;
    if (outflags & AS_ATT)      /* if to write table header */
      as_write(attset, out, k, infout);
    k = AS_INST|(k & ~AS_ATT);  /* write the attribute names */
  }                             /* to the output file */
  f = AS_INST | (inflags & ~(AS_ATT|AS_DFLT));
  i = ((inflags & AS_DFLT) && !(inflags & AS_ATT))
    ? 0 : as_read(attset, in, f);
  while (i == 0) {              /* record read loop */
    res.class = (fbc) ? fbc_exec(fbc, NULL, &res.prob)
                      : nbc_exec(nbc, NULL, &res.prob);
    if (clscnt <= 2) {          /* if this is a two class problem */
      if (res.class <= 0) {     /* check and adapt class 0 result */
	if (res.prob <   thresh) {
          res.class = 1; res.prob = 1 -res.prob; } }
      else {                    /* check and adapt class 1 result */
        if (res.prob < 1-thresh) {
          res.class = 0; res.prob = 1 -res.prob; }
      }                         /* (classify as class 0 if prob. */
    }                           /* of this class is >= threshold) */
    wgt = as_getwgt(attset);    /* classify tuple */
    tplwgt += wgt; tplcnt++;    /* count tuple and sum its weight */
    if (res.class != att_inst(res.att)->i)
      errcnt += wgt;            /* count classification errors */
    if (out && (as_write(attset, out, k, infout) != 0))
      error(E_FWRITE, fn_out);  /* write tuple to output file */
    i = as_read(attset, in, f); /* try to read the next record */
  }
  if (i < 0) {                  /* if an error occurred, */
    err = as_err(attset);       /* get the error information */
    tplcnt += (inflags & (AS_ATT|AS_DFLT)) ? 1 : 2;
    io_error(i, fn_tab, tplcnt, err->s, err->fld, err->exp);
    error(1);                   /* print an error message */
  }                             /* and abort the program */
  if (in != stdin) fclose(in);  /* close the table file and */
  in = NULL;                    /* clear the file variable */
  if (out && (out != stdout)) { /* if an output file exists, */
    i = fclose(out); out = NULL;/* close the output file */
    if (i) error(E_FWRITE, fn_out);
  }                             /* print a sucess message */
  fprintf(stderr, "[%d/%g tuple(s)] done.\n", tplcnt, tplwgt);
  if (att_getmark(res.att) >= 0) {
    fprintf(stderr, "%g error(s) (%.2f%%)\n", errcnt,
            (tplwgt > 0) ? 100*(errcnt /tplwgt) : 0);
  }                             /* if class found, print errors */

  /* --- clean up --- */
  #ifndef NDEBUG
  if (fbc) fbc_delete(fbc, 1);  /* delete full  Bayes classifier */
  if (nbc) nbc_delete(nbc, 1);  /* or     naive Bayes classifier */
  #endif                        /* and underlying attribute set */
  #ifdef STORAGE
  showmem("at end of program"); /* check memory usage */
  #endif
  return 0;                     /* return 'ok' */
}  /* main() */
