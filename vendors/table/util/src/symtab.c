/*----------------------------------------------------------------------
  File    : symtab.c
  Contents: symbol table management
  Author  : Christian Borgelt
  History : 1995.10.22 file created
            1995.10.30 functions made independent of symbol data
            1995.11.26 symbol types and visibility levels added
            1996.01.04 st_clear added
            1996.02.27 st_insert modified
            1996.06.28 dynamic bin array enlargement added
            1996.07.04 bug in hash bin reorganization removed
            1997.04.01 functions st_clear and st_remove combined
            1997.07.29 minor improvements
            1997.08.05 minor improvements
            1997.11.16 some comments improved
            1998.02.06 default table sizes changed
            1998.05.31 list of all symbols removed
            1998.06.20 deletion function moved to st_create
            1998.07.14 minor improvements
            1998.09.01 bug in function _sort removed, assertions added
            1998.09.25 hash function improved
            1998.09.28 types ULONG and CCHAR removed, st_stats added
            1999.02.04 long int changed to int
            1999.11.10 name/identifier map management added
            2003.08.15 renamed new to nel in st_insert (C++ compat.)
            2004.12.15 function nim_trunc added
            2004.12.28 bug in function nim_trunc fixed
            2008.08.11 function nim_getid added
----------------------------------------------------------------------*/
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <limits.h>
#include <assert.h>
#include "symtab.h"
#ifdef NIMAPFN
#include "arrays.h"
#endif
#ifdef STORAGE
#include "storage.h"
#endif

/*----------------------------------------------------------------------
  Preprocessor Definitions
----------------------------------------------------------------------*/
#define DFLT_INIT     1023      /* default initial hash table size */
#if (INT_MAX > 32767)
#define DFLT_MAX   1048575      /* default maximal hash table size */
#else
#define DFLT_MAX     16383      /* default maximal hash table size */
#endif
#define BLKSIZE        256      /* block size for identifier array */

/*----------------------------------------------------------------------
  Default Hash Function
----------------------------------------------------------------------*/

static unsigned _hdflt (const char *name, int type)
{                               /* --- default hash function */
  register unsigned h = type;   /* hash value */

  while (*name) h ^= (h << 3) ^ (unsigned)(*name++);
  return h;                     /* compute hash value */
}  /* _hdflt() */

/*----------------------------------------------------------------------
  Auxiliary Functions
----------------------------------------------------------------------*/

static void _delsym (SYMTAB *tab)
{                               /* --- delete all symbols */
  int i;                        /* loop variable */
  STE *ste, *tmp;               /* to traverse the symbol list */

  for (i = tab->size; --i >= 0; ) {  /* traverse the bin array */
    ste = tab->bins[i];         /* get the next bin list, */
    tab->bins[i] = NULL;        /* clear the bins array entry, */
    while (ste) {               /* and traverse the bin list */
      tmp = ste;                /* note the symbol to delete */
      ste = ste->succ;          /* and get the next symbol */
      if (tab->delfn) tab->delfn(tmp +1);
      free(tmp);                /* if a deletion function is given, */
    }                           /* call it and then deallocate */
  }                             /* the symbol table element */
}  /* _delsym() */

/*--------------------------------------------------------------------*/

static STE** _merge (STE *in[], int cnt[], STE **out)
{                               /* --- merge two lists into one */
  int k;                        /* index of input list */

  do {                          /* compare and merge loop */
    k = (in[0]->level > in[1]->level) ? 0 : 1;
    *out  = in[k];              /* append the element on the higher */
    out   = &(*out)->succ;      /* level to the output list and */
    in[k] = *out;               /* remove it from the input list */
  } while (--cnt[k] > 0);       /* while both lists are not empty */
  *out = in[k ^= 1];            /* append remaining elements */
  while (--cnt[k] >= 0)         /* while not at the end of the list */
    out = &(*out)->succ;        /* go to the successor element */
  in[k] = *out;                 /* set new start of the input list */
  *out  = NULL;                 /* terminate the output list and */
  return out;                   /* return new end of the output list */
}  /* _merge() */

/*--------------------------------------------------------------------*/

static STE* _sort (STE *list)
{                               /* --- sort a hash bin list */
  STE *ste;                     /* to traverse the list, buffer */
  STE *in[2], *out[2];          /* input and output lists */
  STE **end[2];                 /* ends of output lists */
  int cnt[2];                   /* number of elements to merge */
  int run;                      /* run length in input lists */
  int rem;                      /* elements in remainder collection */
  int oid;                      /* index of output list */

  if (!list) return list;       /* empty lists need not to be sorted */
  oid = 0; out[0] = list;       /* traverse list elements */
  for (ste = list->succ; ste; ste = ste->succ)
    if ((oid ^= 1) == 0) list = list->succ;
  out[1] = list->succ;          /* split list into two equal parts */
  list   = list->succ = NULL;   /* initialize remainder collection */
  run    = 1; rem = 0;          /* and run length */
  while (out[1]) {              /* while there are two lists */
    in [0] = out[0]; in [1] = out[1];  /* move output list to input */
    end[0] = out;    end[1] = out+1;   /* reinitialize end pointers */
    out[1] = NULL;   oid    = 0;       /* start with 1st output list */
    do {                        /* merge loop */
      cnt[0]   = cnt[1] = run;  /* merge run elements from the */
      end[oid] = _merge(in, cnt, end[oid]);     /* input lists */
      oid ^= 1;                 /* toggle index of output list */
    } while (in[1]);            /* while both lists are not empty */
    if (in[0]) {                /* if there is one input list left */
      if (!list)                /* if there is no rem. collection, */
        list = in[0];           /* just note the rem. input list */
      else {                    /* if there is a rem. collection, */
        cnt[0] = run; cnt[1] = rem; in[1] = list;
        _merge(in, cnt, &list); /* merge it and the input list to */
      }                         /* get the new renmainder collection */
      rem += run;               /* there are now run more elements */
    }                           /* in the remainder collection */
    run <<= 1;                  /* double run length */
  }  /* while (out[1]) .. */
  if (rem > 0) {                /* if there is a rem. collection */
    in[0] = out[0]; cnt[0] = run;
    in[1] = list;   cnt[1] = rem;
    _merge(in, cnt, out);       /* merge it to the output list */
  }                             /* and store the result in out[0] */
  return out[0];                /* return the sorted list */
}  /* _sort() */

/*--------------------------------------------------------------------*/

static void _reorg (SYMTAB *tab)
{                               /* --- reorganize a hash table */
  int i;                        /* loop variable */
  int size;                     /* new bin array size */
  STE **p;                      /* new bin array, buffer */
  STE *ste;                     /* to traverse symbol table elements */
  STE *list = NULL;             /* list of all symbols */

  size = (tab->size << 1) +1;   /* calculate new bin array size */
  if (size > tab->max)          /* if new size exceeds maximum, */
    size = tab->max;            /* set the maximal size */
  for (p = &list, i = tab->size; --i >= 0; ) {
    *p = tab->bins[i];          /* traverse the bin array and */
    while (*p) p = &(*p)->succ; /* link all bin lists together */
  }                             /* (collect symbols) */
  p = (STE**)realloc(tab->bins, size *sizeof(STE*));
  if (!p) return;               /* enlarge the bin array */
  tab->bins = p;                /* set new bin array size */
  tab->size = size;             /* and its size */
  for (p += i = size; --i >= 0; )
    *--p = NULL;                /* clear the hash bins */
  while (list) {                /* traverse list of all symbols */
    ste = list; list = list->succ;           /* get next symbol */
    i   = tab->hash(ste->name, ste->type) %size;
    ste->succ = tab->bins[i];   /* compute the hash bin index */
    tab->bins[i] = ste;         /* and insert the symbol at */
  }                             /* the head of the bin list */
  for (i = size; --i >= 0; )    /* sort all bin lists according to */
    tab->bins[i] = _sort(tab->bins[i]);    /* the visibility level */
}  /* _reorg() */

/*----------------------------------------------------------------------
  Symbol Table Functions
----------------------------------------------------------------------*/

SYMTAB* st_create (int init, int max, HASHFN hash, OBJFN delfn)
{                               /* --- create a symbol table */
  SYMTAB *tab;                  /* created symbol table */

  if (init <= 0) init = DFLT_INIT;  /* check and adapt the initial */
  if (max  <= 0) max  = DFLT_MAX;   /* and maximal bin array size */
  tab = (SYMTAB*)malloc(sizeof(SYMTAB));
  if (!tab) return NULL;        /* allocate symbol table body */
  tab->bins = (STE**)calloc(init, sizeof(STE*));
  if (!tab->bins) { free(tab); return NULL; }
  tab->level = tab->cnt = 0;    /* allocate the hash bin array */
  tab->size  = init;            /* and initialize fields */
  tab->max   = max;             /* of symbol table body */
  tab->hash  = (hash) ? hash : _hdflt;
  tab->delfn = delfn;
  tab->vsz   = INT_MAX;
  tab->ids   = NULL;
  return tab;                   /* return created symbol table */
}  /* st_create() */

/*--------------------------------------------------------------------*/

void st_delete (SYMTAB *tab)
{                               /* --- delete a symbol table */
  assert(tab && tab->bins);     /* check argument */
  _delsym(tab);                 /* delete all symbols, */
  free(tab->bins);              /* the hash bin array, */
  if (tab->ids) free(tab->ids); /* the identifier array, */
  free(tab);                    /* and the symbol table body */
}  /* st_delete() */

/*--------------------------------------------------------------------*/

void* st_insert (SYMTAB *tab, const char *name, int type,
                 unsigned size)
{                               /* --- insert a symbol */
  unsigned h;                   /* hash value */
  int i;                        /* index of hash bin */
  STE *ste;                     /* to traverse a bin list */
  STE *nel;                     /* new symbol table element */

  assert(tab && name            /* check the function arguments */
      && ((size >= sizeof(int)) || (tab->vsz == INT_MAX)));
  if ((tab->cnt /4 > tab->size) /* if the bins are rather full and */
  &&  (tab->size   < tab->max)) /* table does not have maximal size, */
    _reorg(tab);                /* reorganize the hash table */

  h   = tab->hash(name, type);  /* compute the hash value and */
  i   = h % tab->size;          /* the index of the hash bin */
  ste = tab->bins[i];           /* get first element in the bin */
  while (ste) {                 /* traverse the bin list */
    if ((type == ste->type)     /* if symbol found */
    &&  (strcmp(name, ste->name) == 0))
      break;                    /* abort the loop */
    ste = ste->succ;            /* otherwise get the successor */
  }                             /* element in the hash bin */
  if (ste                       /* if symbol found on current level */
  && (ste->level == tab->level))
    return EXISTS;              /* return 'symbol exists' */

  #ifdef NIMAPFN                /* if name/identifier map management */
  if (tab->cnt >= tab->vsz) {   /* if the identifier array is full */
    int vsz, **tmp;             /* (new) id array and its size */
    vsz = tab->vsz +((tab->vsz > BLKSIZE) ? tab->vsz >> 1 : BLKSIZE);
    tmp = (int**)realloc(tab->ids, vsz *sizeof(int*));
    if (!tmp) return NULL;      /* resize the identifier array and */
    tab->ids = tmp; tab->vsz = vsz;  /* set new array and its size */
  }                             /* (no resizing for symbol tables */
  #endif                        /* since then tab->vsz = MAX_INT) */

  nel = (STE*)malloc(sizeof(STE) +size +strlen(name) +1);
  if (!nel) return NULL;        /* allocate memory for new symbol */
  nel->name    = (char*)(nel+1) +size;         /* and organize it */
  strcpy(nel->name, name);      /* note the symbol name, */
  nel->type    = type;          /* the symbol type, and the */
  nel->level   = tab->level;    /* current visibility level */
  nel->succ    = tab->bins[i];  /* insert new symbol at the head */
  tab->bins[i] = nel++;         /* of the hash bin list */
  #ifdef NIMAPFN                /* if name/identifier maps are */
  if (tab->ids) {               /* supported and this is such a map */
    tab->ids[tab->cnt] = (int*)nel;
    *(int*)nel = tab->cnt;      /* store the new symbol */
  }                             /* in the identifier array */
  #endif                        /* and set the symbol identifier */
  tab->cnt++;                   /* increment the symbol counter */
  return nel;                   /* return pointer to data field */
}  /* st_insert() */

/*--------------------------------------------------------------------*/

int st_remove (SYMTAB *tab, const char *name, int type)
{                               /* --- remove a symbol/all symbols */
  int i;                        /* index of hash bin */
  STE **p, *ste;                /* to traverse a hash bin list */

  assert(tab);                  /* check for a valid symbol table */

  /* --- remove all symbols --- */
  if (!name) {                  /* if no symbol name given */
    _delsym(tab);               /* delete all symbols */
    tab->cnt = tab->level = 0;  /* reset visibility level */
    return 0;                   /* and symbol counter */
  }                             /* and return 'ok' */

  /* --- remove one symbol --- */
  i = tab->hash(name, type) % tab->size;
  p = tab->bins +i;             /* compute index of hash bin */
  while (*p) {                  /* and traverse the bin list */
    if (((*p)->type == type)    /* if symbol found */
    &&  (strcmp(name, (*p)->name) == 0))
      break;                    /* abort loop */
    p = &(*p)->succ;            /* otherwise get successor */
  }                             /* in hash bin */
  ste = *p;                     /* if the symbol does not exist, */
  if (!ste) return -1;          /* abort the function */
  *p = ste->succ;               /* remove symbol from hash bin */
  if (tab->delfn) tab->delfn(ste +1);   /* delete user data */
  free(ste);                    /* and symbol table element */
  tab->cnt--;                   /* decrement symbol counter */
  return 0;                     /* return 'ok' */
}  /* st_remove() */

/*--------------------------------------------------------------------*/

void* st_lookup (SYMTAB *tab, const char *name, int type)
{                               /* --- look up a symbol */
  int i;                        /* index of hash bin */
  STE *ste;                     /* to traverse a hash bin list */

  assert(tab && name);          /* check arguments */
  i   = tab->hash(name, type) % tab->size;
  ste = tab->bins[i];           /* compute index of hash bin */
  while (ste) {                 /* and traverse the bin list */
    if ((ste->type == type)     /* if symbol found */
    &&  (strcmp(name, ste->name) == 0))
      return ste +1;            /* return pointer to assoc. data */
    ste = ste->succ;            /* otherwise get the successor */
  }                             /* in the hash bin */
  return NULL;                  /* return 'not found' */
}  /* st_lookup() */

/*--------------------------------------------------------------------*/

void st_endblk (SYMTAB *tab)
{                               /* --- remove one visibility level */
  int i;                        /* loop variable */
  STE *ste, *tmp;               /* to traverse bin lists */

  assert(tab);                  /* check for a valid symbol table */
  if (tab->level <= 0) return;  /* if on level 0, abort */
  for (i = tab->size; --i >= 0; ) {  /* traverse the bin array */
    ste = tab->bins[i];         /* get the next bin list */
    while (ste                  /* and remove all symbols */
    &&    (ste->level >= tab->level)) {  /* of higher level */
      tmp = ste;                /* note symbol and */
      ste = ste->succ;          /* get successor */
      if (tab->delfn) tab->delfn(tmp +1);
      free(tmp);                /* delete user data and */
      tab->cnt--;               /* symbol table element */
    }                           /* and decrement symbol counter */
    tab->bins[i] = ste;         /* set new start of bin list */
  }
  tab->level--;                 /* go up one level */
}  /* st_endblk() */

/*--------------------------------------------------------------------*/
#ifndef NDEBUG

void st_stats (const SYMTAB *tab)
{                               /* --- compute and print statistics */
  const STE *ste;               /* to traverse hash bin lists */
  int i;                        /* loop variable */
  int used;                     /* number of used hash bins */
  int len;                      /* length of current bin list */
  int min, max;                 /* min. and max. bin list length */
  int cnts[10];                 /* counter for bin list lengths */

  assert(tab);                  /* check for a valid symbol table */
  min = INT_MAX; max = used = 0;/* initialize variables */
  for (i = 10; --i >= 0; ) cnts[i] = 0;
  for (i = tab->size; --i >= 0; ) { /* traverse the bin array */
    len = 0;                    /* determine bin list length */
    for (ste = tab->bins[i]; ste; ste = ste->succ) len++;
    if (len > 0) used++;        /* count used hash bins */
    if (len < min) min = len;   /* determine minimal and */
    if (len > max) max = len;   /* maximal list length */
    cnts[(len >= 9) ? 9 : len]++;
  }                             /* count list length */
  printf("number of symbols   : %d\n", tab->cnt);
  printf("number of hash binss: %d\n", tab->size);
  printf("used hash bins      : %d\n", used);
  printf("minimal list length : %d\n", min);
  printf("maximal list length : %d\n", max);
  printf("average list length : %g\n", (double)tab->cnt/tab->size);
  printf("ditto, of used bins : %g\n", (double)tab->cnt/used);
  printf("length distribution :\n");
  for (i = 0; i < 9; i++) printf("%3d ", i);
  printf(" >8\n");
  for (i = 0; i < 9; i++) printf("%3d ", cnts[i]);
  printf("%3d\n", cnts[9]);
}  /* st_stats() */

#endif
/*----------------------------------------------------------------------
  Name/Identifier Map Functions
----------------------------------------------------------------------*/
#ifdef NIMAPFN

NIMAP* nim_create (int init, int max, HASHFN hash, OBJFN delfn)
{                               /* --- create a name/identifier map */
  NIMAP *nim;                   /* created name/identifier map */

  nim = st_create(init, max, hash, delfn);
  if (!nim) return NULL;        /* create a name/identifier map */
  nim->vsz = 0;                 /* and clear the id. array size */
  return nim;                   /* return created name/id map */
}  /* nim_create() */

/*--------------------------------------------------------------------*/

int nim_getid (NIMAP *nim, const char *name)
{                               /* --- get an item identifier */
  STE *p = nim_byname(nim, name);
  return (p) ? *(int*)p : -1;   /* look up the given name and */
}  /* nim_getid() */            /* return its identifier or -1 */

/*--------------------------------------------------------------------*/

void nim_sort (NIMAP *nim, CMPFN cmpfn, void *data, int *map, int dir)
{                               /* --- sort name/identifier map */
  int i;                        /* loop variable */
  int **p;                      /* to traverse the value array */

  assert(nim && cmpfn);         /* check the function arguments */
  ptr_qsort(nim->ids, nim->cnt, cmpfn, data);
  if (!map) {                   /* if no conversion map is requested */
    for (p = nim->ids +(i = nim->cnt); --i >= 0; )
      **--p = i; }              /* just set new identifiers */
  else {                        /* if a conversion map is requested, */
    p = nim->ids +(i = nim->cnt);       /* traverse the sorted array */
    if (dir < 0)                /* if backward map (i.e. new -> old) */
      while (--i >= 0) { map[i] = **--p; **p = i; }
    else                        /* if forward  map (i.e. old -> new) */
      while (--i >= 0) { map[**--p] = i; **p = i; }
  }                             /* (build conversion map) */
}  /* nim_sort() */

/*--------------------------------------------------------------------*/

void nim_trunc (NIMAP *nim, int n)
{                               /* --- truncate name/identifier map */
  int *id;                      /* to access the identifiers */

  while (nim->cnt > n) {        /* while to remove mappings */
    id = nim->ids[nim->cnt -1]; /* get the identifier object */
    st_remove(nim, st_name(id), 0);
  }                             /* remove the symbol table element */
}  /* nim_trunc() */

#endif
