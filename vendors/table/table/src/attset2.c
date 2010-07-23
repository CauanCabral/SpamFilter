/*----------------------------------------------------------------------
  File    : attset2.c
  Contents: attribute set management, read/write and parse functions
  Author  : Christian Borgelt
  History : 1995.10.26 file created
            1995.11.03 functions as_read and as_write added
            1995.11.25 functions as_write extended
            1995.11.29 output restricted to maxlen characters per line
            1996.02.22 functions as_read and as_write redesigned
            1996.02.23 function as_desc added
            1996.02.24 functions as_save and as_load added
            1997.02.26 default attribute name generation added
            1997.08.01 restricted read/write/describe added
            1997.08.02 additional information output added
            1997.09.26 error code added to as_err structure
            1998.01.04 scan functions moved to module 'tabscan'
            1998.01.06 read/write functions made optional
            1998.01.10 variable null value characters added
            1998.03.06 alignment of last field removed
            1998.03.13 adding values from function as_read simplified
            1998.08.23 attribute creation and deletion functions added
            1998.09.01 several assertions added
            1998.09.06 second major redesign completed
            1998.09.14 attribute selection in as_write/as_desc improved
            1998.09.17 attribute selection improvements completed
            1999.02.04 long int changed to int
            1999.02.12 attribute reading in mode AS_DFLT improved
            1999.03.23 attribute name existence check made optional
            2000.02.03 error reporting bug in function as_read fixed
            2000.11.22 functions sc_form and sc_len exported
            2001.05.13 acceptance of null values made optional
            2001.06.23 module split into two files
            2001.10.04 bug in value range reading removed
            2002.01.22 parser functions moved to a separate file
            2002.05.31 bug in field reporting in as_read fixed
            2002.06.18 check for valid intervals added to as_desc
            2003.07.22 bug in as_write (output of NV_REAL) fixed
            2004.05.21 semantics of null value reading changed
            2006.01.17 bug in function as_write fixed (RDORD vs. RANGE)
            2006.10.06 adapted to improved function ts_next
            2007.02.13 adapted to redesigned module tabscan
            2007.02.17 directions added, weight description modified
----------------------------------------------------------------------*/
#include <stdio.h>
#include <stdlib.h>
#include <stdarg.h>
#include <string.h>
#include <assert.h>
#include "attset.h"
#ifdef STORAGE
#include "storage.h"
#endif

/*----------------------------------------------------------------------
  Preprocessor Definitions
----------------------------------------------------------------------*/
#define BLKSIZE       16        /* block size for vectors */

/*----------------------------------------------------------------------
  Load/Save Functions
----------------------------------------------------------------------*/

int as_save (const ATTSET *set, FILE *file)
{                               /* --- save instances and weight */
  int i;                        /* loop variable */
  ATT **p;                      /* to traverse attribute vector */

  assert(set && file);          /* check the function arguments */
  p = set->atts;                /* write attribute instantiations */
  for (i = set->attcnt; --i >= 0; p++)
    fwrite(&(*p)->inst, sizeof(INST), 1, file);
  fwrite(&set->weight, sizeof(float), 1, file);
  return ferror(file);          /* check for write error */
}  /* as_save() */

/*--------------------------------------------------------------------*/

int as_load (ATTSET *set, FILE *file)
{                               /* --- load instances and weight */
  int i;                        /* loop variable */
  ATT **p;                      /* to traverse attribute vector */

  assert(set && file);          /* check the function arguments */
  p = set->atts;                /* read attribute instantiations */
  for (i = set->attcnt; --i >= 0; p++)
    fread(&(*p)->inst, sizeof(INST), 1, file);
  fread(&set->weight, sizeof(float), 1, file);
  if (ferror(file)) return -1;  /* check for a read error */
  return (feof(file)) ? 1 : 0;  /* check for end of file */
}  /* as_load() */

/*----------------------------------------------------------------------
  Read/Write Functions
----------------------------------------------------------------------*/
#ifdef AS_RDWR

CCHAR* as_chars (ATTSET *set, CCHAR *recseps, CCHAR *fldseps,
                 CCHAR *blanks, CCHAR *nullchs, CCHAR *comment)
{                               /* --- set special characters */
  assert(set);                  /* check the function argument */
  if (blanks)                   /* set blank characters */
    set->chars[0] = ts_chars(set->tscan, TS_BLANK,  blanks);
  if (fldseps)                  /* set field separators */
    set->chars[1] = ts_chars(set->tscan, TS_FLDSEP, fldseps);
  if (recseps)                  /* set record separators */
    set->chars[2] = ts_chars(set->tscan, TS_RECSEP, recseps);
  if (nullchs)                  /* set null value characters */
    set->chars[3] = ts_chars(set->tscan, TS_NULL,   nullchs);
  if (comment)                  /* set comment characters */
    ts_chars(set->tscan, TS_COMMENT, comment);
  return set->chars;            /* return the output characters */
}  /* as_chars() */

/*--------------------------------------------------------------------*/

static int _rderr (ATTSET *set, int code, int fld, int exp, char *s)
{                               /* --- set read error information */
  set->err->code = code;        /* note the error code and */
  set->err->rec  = 0;           /* clear the record number */
  set->err->fld  = fld;         /* note the current field and */
  set->err->exp  = exp;         /* the expected number of fields */
  if (s) sc_format(set->buf, s, 1);
  else   set->buf[0] = '\0';    /* format the string value */
  return code;                  /* return the error code */
}  /* _rderr() */

/*--------------------------------------------------------------------*/

int as_read (ATTSET *set, FILE *file, int mode, ...)
{                               /* --- read attributes/instances */
  va_list args;                 /* list of variable arguments */
  int     i, r;                 /* loop variable, buffer */
  int     off, cnt, end;        /* range of attributes */
  char    *name;                /* attribute name */
  int     attid;                /* attribute identifier */
  ATT     *att, **p;            /* to traverse the attributes */
  int     vsz;                  /* field vector size */
  int     *fld;                 /* pointer to/in field vector */
  double  wgt;                  /* instantiation weight */
  char    *s;                   /* end pointer for conversion */
  int     d;                    /* delimiter type */
  INST    *inst;                /* dummy instance */
  char    buf[AS_MAXLEN+1];     /* read buffer */
  char    dflt[32];             /* buffer for default name */

  assert(set);                  /* check the function argument */
  if (!file) {                  /* if no file is given */
    #if defined AS_FLDS || defined AS_RDWR
    if (set->flds) { free(set->flds); set->flds = NULL; }
    set->fldcnt = set->fldvsz = 0;
    #endif                      /* delete the field map */
    return set->err->code = 0;  /* and abort the function */
  }
  inst = (mode & AS_NOXVAL) ? (void*)1 : NULL;

  /* --- get range of attributes --- */
  if (mode & AS_RANGE) {        /* if an index range is given */
    va_start(args, mode);       /* start variable argument evaluation */
    off = va_arg(args, int);    /* get offset to first value */
    cnt = va_arg(args, int);    /* and number of values */
    va_end(args);               /* end variable argument evaluation */
    i = set->attcnt -off;       /* check and adapt */
    if (cnt > i) cnt = i;       /* number of values */
    assert((off >= 0) && (cnt >= 0)); }
  else {                        /* if no index range given */
    off = 0; cnt = set->attcnt; /* get full index range */
  }
  end = off +cnt;               /* get index of last attribute */

  /* --- read attributes --- */
  if (mode & (AS_ATT|AS_DFLT)){ /* if to read attributes */
    for (p = set->atts +(i = set->attcnt); --i >= 0; )
      (*--p)->read = 0;         /* clear all read flags */
    vsz = set->fldvsz;          /* get size of field vector */
    cnt = set->fldcnt = 0;      /* initialize field counter */
    do {                        /* traverse the table fields */
      d = ts_next(set->tscan, file, buf, AS_MAXLEN);
      if (d == TS_ERR)          /* read the next field */
        return _rderr(set, E_FREAD, cnt+1, 0, NULL);
      if (d == TS_EOF) break;   /* check for end of file */
      if ((buf[0] == 0)         /* check whether the field */
      && !(mode & AS_DFLT))     /* is empty and not data */
        return _rderr(set, E_EMPFLD, cnt+1, 0, NULL);
      if ((mode & AS_WEIGHT) && (d == TS_REC))
        break;                  /* skip weight in last field */
      if (cnt >= vsz) {         /* if the field vector is full */
        vsz += (vsz > BLKSIZE) ? (vsz >> 1) : BLKSIZE;
        fld = (int*)realloc(set->flds, vsz *sizeof(int));
        if (!fld) return _rderr(set, E_NOMEM, cnt+1, 0, NULL);
        set->flds   = fld;      /* resize the field vector */
        set->fldvsz = vsz;      /* and set the new vector */
      }                         /* as well as its size */
      if (mode & AS_DFLT)       /* in default mode create a name */
        sprintf(name = dflt, "%d", cnt+1);
      else      name = buf;     /* otherwise use the name read */
      attid = as_attid(set, name);  /* get the attribute id */
      if (attid >= 0) {         /* if the attribute exists, */
        att = set->atts[attid]; /* get the attribute and */
        if (att->read)          /* check its read flag */
          return _rderr(set, E_DUPFLD, cnt+1, 0, name);
        if ((attid <  off)      /* if the attribute */
        ||  (attid >= end)      /* is out of range */
        ||  ((mode & AS_MARKED) /* or if in marked mode and */
        &&   (att->mark < 0)))  /* the attribute is not marked, */
          attid     = -1;       /* skip this attribute, */
        else {                  /* otherwise (if to read attribute) */
          att->read = -1;       /* set the read flag */
        } }                     /* if the attribute does not exist */
      else if (mode & AS_NOXATT)/* if not to extend the att. set, */
        att = NULL;             /* invalidate the attribute */
      else {                    /* if to extend the attribute set */
        att = att_create(name, AT_NOM);
        if (!att) return _rderr(set, E_NOMEM, cnt+1, 0, NULL);
        if (as_attadd(set, att) != 0) { att_delete(att);
          return _rderr(set, E_NOMEM, cnt+1, 0, NULL); }
        attid     = att->id;    /* create an attribute, add it to */
        att->read = -1;         /* the set, get its identifier, */
      }                         /* and set the read flag */
      set->flds[cnt++] = attid; /* set field mapping */
      if ((mode & AS_DFLT)      /* if to use a default header, */
      &&  (attid >= 0)) {       /* set the attribute value read */
        if (buf[0] == '\0') {   /* if the value is null, */
          if (mode & AS_NONULL) /* check whether nulls are allowed */
            return _rderr(set, E_VALUE, cnt, 0, buf);
          if      (att->type == AT_REAL) att->inst.f = NV_REAL;
          else if (att->type == AT_INT)  att->inst.i = NV_INT;
          else                           att->inst.i = NV_NOM; }
        else {                  /* if the value is not null */
          r = att_valadd(att, buf, inst);
          if (r >= 0) continue; /* add the value to the attribute */
          if (r >= -1) return _rderr(set, E_NOMEM, cnt, 0, NULL);
          else         return _rderr(set, E_VALUE, cnt, 0, buf);
        }                       /* if the value cannot be added, */
      }                         /* abort with an error code */
    } while (d != TS_REC);      /* while not at end of record */
    for (p = set->atts +(i = off); i < end; i++) {
      att = *p++;               /* traverse the attributes, */
      if (att->read) continue;  /* but skip read attributes */
      if (!(mode & AS_MARKED)   /* if there is an unread attribute */
      ||  (att->mark > 0))      /* that has to be read, abort */
        return _rderr(set, E_MISFLD, cnt, 0, att->name);
      if ((mode & AS_MARKED)    /* if an unread attribute is marked */
      &&  (att->mark == 0))     /* as optional (read if present), */
        att->mark = -1;         /* adapt the attribute marker */
    }                           /* to indicate that it is missing */
    set->fldcnt = cnt;          /* set number of fields */
    return set->err->code = 0;  /* and return 'ok' */
  }                             /* (end of read attributes) */

  /* --- read instances --- */
  cnt = set->fldcnt;            /* get number of fields and */
  if (cnt > 0) fld = set->flds; /* a pointer to the field vector */
  else       { fld = NULL; cnt = set->attcnt; }
  d = TS_FLD;                   /* set default delimiter type */
  for (i = 0; (i < cnt) && (d == TS_FLD); i++) {
    d = ts_next(set->tscan, file, buf, AS_MAXLEN);
    if (d <= TS_EOF) {          /* get the next value */
      if (d == TS_ERR) return _rderr(set, E_FREAD, i+1, 0, NULL);
      if (i <= 0)      return set->err->code = 1;
    }                           /* if on first field, abort */
    attid = (fld) ? *fld++ : i; /* get attribute identifier */
    if ((attid < off) || (attid >= end))
      continue;                 /* skip attributes out of range */
    att = set->atts[attid];     /* get attribute */
    if ((mode & AS_MARKED)      /* if in marked mode and */
    &&  (att->mark < 0))        /* attribute is not marked, */
      continue;                 /* skip this field */
    if (buf[0] == '\0') {       /* if the value is null */
      if (mode & AS_NONULL)     /* check whether nulls are allowed */
        return _rderr(set, E_VALUE, i+1, 0, buf);
      if      (att->type == AT_REAL) att->inst.f = NV_REAL;
      else if (att->type == AT_INT)  att->inst.i = NV_INT;
      else                           att->inst.i = NV_NOM; }
    else {                      /* if the value is not null */
      r = att_valadd(att, buf, inst);
      if (r >=  0) continue;    /* add the value to the attribute */
      if (r >= -1) return _rderr(set, E_NOMEM, i+1, 0, NULL);
      else         return _rderr(set, E_VALUE, i+1, 0, buf);
    }                           /* if the value cannot be added, */
  }  /* for (i = 0; .. */       /* abort with an error code */
  if (!(mode & AS_WEIGHT))      /* if there is no weight field, */
    set->weight = 1.0F;         /* set instantiation weight to 1 */
  else if (d != TS_FLD)         /* if no weight field is available */
    return _rderr(set, E_FLDCNT, cnt, cnt+1, NULL);
  else {                        /* if weight field is available */
    d = ts_next(set->tscan, file, buf, AS_MAXLEN);
    if (d == TS_ERR)            /* read the weight field */
      return _rderr(set, E_FREAD, i+1, 0, NULL);
    wgt = strtod(buf, &s);      /* convert to float and check value */
    if ((s == buf) || (*s != '\0')
    ||  (wgt < 0)  || (wgt > FLT_MAX))
      return _rderr(set, E_VALUE, i+1, 0, buf);
    set->weight = (float)wgt;   /* set the instantiation weight */
  }
  if ((i <  cnt)                /* if too few */
  ||  (d == TS_FLD)) {          /* or too many fields found */
    cnt += ((mode & AS_WEIGHT) ? 1 : 0);
    return _rderr(set, E_FLDCNT, (i < cnt) ? i : cnt+1, cnt, NULL);
  }                             /* abort with an error code */
  return set->err->code = 0;    /* return 'ok' */
}  /* as_read() */

/*--------------------------------------------------------------------*/

int as_write (ATTSET *set, FILE *file, int mode, ...)
{                               /* --- write attributes/instances */
  va_list  args;                /* list of variable arguments */
  int      i, k;                /* loop variables, buffers */
  int      off, cnt, end;       /* range of attributes */
  ATT      *att;                /* to traverse attributes */
  int      attid;               /* attribute identifier */
  int      *fld;                /* pointer to/in field vector */
  CCHAR    *name;               /* name of attribute/value */
  INFOUTFN *infout = 0;         /* add. info. output function */
  char     buf[64];             /* buffer for attribute values */

  assert(set && file);          /* check the function arguments */

  /* --- get range of attributes --- */
  va_start(args, mode);         /* start variable argument evaluation */
  if (mode & AS_RANGE) {        /* if an index range is given */
    off = va_arg(args, int);    /* get offset to first value */
    cnt = va_arg(args, int);    /* and number of values */
    i = set->attcnt -off;       /* check and adapt */
    if (cnt > i) cnt = i;       /* number of values */
    assert((off >= 0) && (cnt >= 0)); }
  else {                        /* if no index range given */
    off = 0; cnt = set->attcnt; /* get full index range */
  }
  end = off +cnt;               /* get end of range of attributes */
  if (mode & (AS_INFO1|AS_INFO2))  /* get additional information */
    infout = va_arg(args, INFOUTFN*);         /* output function */
  va_end(args);                 /* get add. info. output function and */

  /* --- write attributes/values --- */
  if (!(mode & AS_RDORD)        /* if not to write in read order */
  ||  (set->fldcnt <= 0))       /* or no read order is available, */
    fld = NULL;                 /* clear field pointer */
  else {                        /* if to write in read order, */
    fld = set->flds;            /* get field pointer */
    cnt = set->fldcnt;          /* and number of fields */
  }
  name = NULL;                  /* clear name as a first field flag */
  for (i = 0; i < cnt; i++) {   /* traverse fields/attributes */
    attid = (fld) ? *fld++ : (i +off);      /* get next index */
    if ((attid < off) || (attid >= end))
      continue;                 /* skip attributes out of range */
    att = set->atts[attid];     /* get attribute */
    if ((mode & AS_MARKED)      /* if in marked mode and */
    &&  (att->mark < 0))        /* attribute is not marked, */
      continue;                 /* skip this attribute */
    if (name)                   /* if not the first field, */
      putc(set->chars[1], file);/* print field separator */
    if      (mode & AS_ATT)     /* if to write attributes, */
      name = att->name;         /* get attribute name */
    else if (att->type == AT_INT) { /* if integer value */
      if (att->inst.i <= NV_INT)/* if value is null, */
        name = set->chars +3;   /* set null value character */
      else {                    /* if value is known */
        sprintf(buf, "%d", att->inst.i); name = buf;
      } }                       /* format value */
    else if (att->type == AT_REAL) { /* if real value */
      if (att->inst.f <= NV_REAL)  /* if value is null, */
        name = set->chars +3;   /* set null value character */
      else {                    /* if value is known */
        sprintf(buf, "%g", att->inst.f); name = buf;
      } }                       /* format value */
    else {                      /* otherwise (nominal value) */
      k = att->inst.i;          /* get value identifier */
      name = (k < 0) ? set->chars +3 : att->vals[k]->name;
    }                           /* get name of attribute value */
    fputs(name, file);          /* write attribute/value name */
    if ((mode & (AS_ALIGN|AS_ALNHDR))   /* if to align fields and */
    &&  ((i < cnt -1)           /* not on the last field to write */
    ||   (mode & (AS_INFO1|AS_WEIGHT|AS_INFO2)))) {
      k = att->valwd[0];        /* get width of widest value */
      if ((mode & AS_ALNHDR) && (att->attwd[0] > k))
        k = att->attwd[0];      /* adapt with width of att. name and */
      k -= (int)strlen(name);   /* subtract width of current value */
      while (--k >= 0) putc(set->chars[0], file);
    }                           /* pad the field with blanks */
  }                             /* (write normal fields) */

  /* --- write weight and additional information --- */
  if (mode & AS_INFO1) {        /* if to write additional information */
    putc(set->chars[1], file);  /* write field separator */
    infout(set, file, mode, set->chars);
  }                             /* call function to write add. info. */
  if (mode & AS_WEIGHT) {       /* if weight output requested */
    putc(set->chars[1], file);  /* write field separator */
    if (mode & AS_ATT) putc('#', file);
    else fprintf(file, "%g", set->weight);
  }                             /* write counter field */
  if (mode & AS_INFO2) {        /* if to write additional information */
    putc(set->chars[1], file);  /* write field separator */
    infout(set, file, mode, set->chars);
  }                             /* call function to write add. info. */
  putc(set->chars[2], file);    /* terminate the record written */
  return ferror(file);          /* check for a write error */
}  /* as_write() */

#endif  /* #ifdef AS_RDWR */
/*----------------------------------------------------------------------
  Description Function
----------------------------------------------------------------------*/

static void _term (ATT *att, FILE *file, int mode)
{                               /* --- terminate attribute output */
  static char* dirs[] = {       /* names of attribute directions */
    "none", "in", "out", "id", "wgt" };

  if ((mode & AS_DIRS)          /* if to print attribute directions */
  &&  (att->dir >= DIR_NONE)    /* and direction is in allowed range, */
  &&  (att->dir <= DIR_WGT)) {  /* print a direction indicator */
    fputs(" : ", file); fputs(dirs[att->dir], file); }
  if (mode & AS_WEIGHT)         /* if to print attribute weights */
    fprintf(file, ", %g", att->weight);
  fputs(";\n", file);           /* terminate the output line */
}  /* _term() */

/*--------------------------------------------------------------------*/

int as_desc (ATTSET *set, FILE *file, int mode, int maxlen, ...)
{                               /* --- describe an attribute set */
  va_list args;                 /* list of variable arguments */
  int     i, k;                 /* loop variables, buffers */
  int     off, cnt;             /* range of attributes */
  ATT     *att;                 /* to traverse attributes */
  int     len;                  /* length of value name */
  int     pos;                  /* position in output line */
  char    buf[4*AS_MAXLEN+4];   /* output buffer */

  assert(set && file);          /* check the function arguments */

  /* --- get range of attributes --- */
  if (mode & AS_RANGE) {        /* if an index range is given */
    va_start(args, maxlen);     /* start variable argument evaluation */
    off = va_arg(args, int);    /* get offset to first value */
    cnt = va_arg(args, int);    /* and number of values */
    va_end(args);               /* end variable argument evaluation */
    i = set->attcnt -off;       /* check and adapt */
    if (cnt > i) cnt = i;       /* number of values */
    assert((off >= 0) && (cnt >= 0)); }
  else {                        /* if no index range given, */
    off = 0; cnt = set->attcnt; /* get full index range */
  }

  /* --- print header (as a comment) --- */
  if (mode & AS_TITLE) {        /* if title flag is set */
    i = k = (maxlen > 0) ? maxlen -2 : 70;
    fputs("/*", file); while (--i >= 0) putc('-', file);
    fprintf(file, "\n  %s\n", set->name);
    while (--k >= 0) putc('-', file); fputs("*/\n", file);
  }                             /* print a title header */
  if (maxlen <= 0) maxlen = INT_MAX;

  /* --- print attribute domains --- */
  for (i = 0; i < cnt; i++) {   /* traverse the attributes */
    att = set->atts[off +i];    /* get the next attribute */
    if ((mode & AS_MARKED)      /* if in marked mode and */
    &&  (att->mark < 0))        /* attribute is not marked, */
      continue;                 /* skip this attribute */
    sc_format(buf, att->name, 0);    /* print the name */
    fprintf(file, "dom(%s) = ", buf);
    if (att->type == AT_INT) {  /* if attribute is integer valued */
      fputs("ZZ", file);        /* print integer numbers sign */
      if ((mode & AS_IVALS)     /* if intervals requested */
      &&  (att->min.i <= att->max.i))
        fprintf(file, " [%d, %d]", att->min.i, att->max.i);
      _term(att, file, mode);   /* print range of values, */
      continue;                 /* terminate output and */
    }                           /* continue with next attribute */
    if (att->type == AT_REAL) { /* if attribute is real valued */
      fputs("IR", file);        /* print real numbers sign */
      if ((mode & AS_IVALS)     /* if intervals requested */
      &&  (att->min.f <= att->max.f))
        fprintf(file, " [%g, %g]", att->min.f, att->max.f);
      _term(att, file, mode);   /* print range of values */
      continue;                 /* terminate output and */
    }                           /* continue with next attribute */
    fputs("{", file);           /* if attribute is nominal */
    pos = att->attwd[1] +10;    /* initialize position */
    for (k = 0; k < att->valcnt; k++) {
      if (k > 0) {              /* if this is not the first value, */
        putc(',', file); pos++; }             /* print a separator */
      len = sc_format(buf, att->vals[k]->name, 0);
      if ((pos +len > maxlen-4) /* if line would get too long, */
      &&  (pos      > 2)) {     /* start a new line */
        fputs("\n ", file); pos = 1; }
      putc(' ', file);          /* print a separating blank and */
      fputs(buf, file);         /* the (scanable) value name, */
      pos += len +1;            /* then calculate new position */
    }
    fputs(" }", file);          /* terminate list of values */
    _term(att, file, mode);     /* terminate the output */
  }

  return ferror(file);          /* check for a write error */
}  /* as_desc() */

/*----------------------------------------------------------------------
  Additional Functions
----------------------------------------------------------------------*/
#ifndef NDEBUG

void as_stats (const ATTSET *set)
{                               /* --- compute and print statistics */
  const ATT *att = NULL;        /* to traverse attributes */
  const VAL *val;               /* to traverse values */
  int   i, k;                   /* loop variables */
  int   cnt;                    /* number of attributes/values */
  int   size;                   /* size of hash table */
  int   used;                   /* number of used hash buckets */
  int   len;                    /* length of current bucket list */
  int   min, max;               /* min. and max. bucket list length */
  int   lcs[10];                /* counter for bucket list lengths */

  assert(set);                  /* check for a valid attribute set */
  if (set->attvsz <= 0) return; /* check hash table size */
  for (i = -1; i < set->attcnt; i++) {
    min = INT_MAX; max = used = 0; /* initialize variables */
    for (k = 10; --k >= 0; ) lcs[k] = 0;
    if (i < 0) {                /* statistics for attribute set */
      printf("attribute set \"%s\"\n", set->name);
      size = set->attvsz;       /* get hash table size */
      cnt  = set->attcnt; }     /* and number of attributes */
    else {                      /* statistics for an attribute */
      att = set->atts[i];       /* get attribute and check it */
      if ((att->type != AT_NOM) || (att->valvsz <= 0)) continue;
      printf("\nattribute \"%s\"\n", att->name);
      size = att->valvsz;       /* print attribute name */
      cnt  = att->valcnt;       /* and get hash table size */
    }                           /* and number of values */
    for (k = size; --k >= 0; ){ /* traverse bucket vector */
      len = 0;                  /* determine bucket list length */
      if (i < 0) for (att = set->htab[k]; att; att = att->succ) len++;
      else       for (val = att->htab[k]; val; val = val->succ) len++;
      if (len > 0) used++;      /* count used hash buckets */
      if (len < min) min = len; /* determine minimal and */
      if (len > max) max = len; /* maximal list length */
      lcs[(len >= 9) ? 9 : len]++;
    }                           /* count list length */
    printf("number of objects     : %d\n", cnt);
    printf("number of hash buckets: %d\n", size);
    printf("used hash buckets     : %d\n", used);
    printf("minimal list length   : %d\n", min);
    printf("maximal list length   : %d\n", max);
    printf("average list length   : %g\n", (double)cnt/size);
    printf("ditto, of used buckets: %g\n", (double)cnt/used);
    printf("length distribution   :\n");
    for (k = 0; k < 9; k++) printf("%3d ", k);
    printf(" >8\n");
    for (k = 0; k < 9; k++) printf("%3d ", lcs[k]);
    printf("%3d\n", lcs[9]);
  }
}  /* as_stats() */

#endif
