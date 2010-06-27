/*----------------------------------------------------------------------
  File    : lists.c
  Contents: some basic list operations
  Author  : Christian Borgelt
  History : 2000.11.02 file created as listops.c
            2008.08.01 renamed to lists.c, some functions added
            2008.08.11 l_merge made a function, main function added
----------------------------------------------------------------------*/
#include <stdio.h>
#include <stdlib.h>
#include <assert.h>
#include "lists.h"

/*----------------------------------------------------------------------
  Functions
----------------------------------------------------------------------*/

void l_delete (void *list, OBJFN delfn)
{                               /* --- delete a list */
  DLLE *le;                     /* buffer for element to delete */

  if (!delfn) {                 /* if no special deletion function */
    while (list) {              /* while the list is not empty */
      le = list; list = le->succ; free(le); }
    return;                     /* remove the first element from */
  }                             /* the list and then delete it */
  while (list) {                /* while the list is not empty */
    le = list; list = le->succ; /* remove the first element from */
    delfn(le); free(le);        /* the list, apply the function, */
  }                             /* and then delete it */
}  /* l_delete() */

/*--------------------------------------------------------------------*/

void* l_reverse (void *list)
{                               /* --- reverse a list */
  DLLE *le = NULL;              /* to traverse the list elements */

  while (list) {                /* while the list is not empty */
    le       = list;            /* get the next list element */
    list     = le->succ;        /* exchange the successor */
    le->succ = le->pred;        /* and the predecessor */
    le->pred = list;            /* of the list element */
  }
  return le;                    /* return a pointer to */
}  /* l_reverse() */            /* the new first element */

/*--------------------------------------------------------------------*/

void* l_append (void *dst, void *src)
{                               /* --- append a list to another */
  DLLE *end;                    /* to traverse the destination list */

  if (!src) return dst;         /* if one of the lists is empty, */
  if (!dst) return src;         /* simply return the other list */
  for (end = dst; end->succ; end = end->succ)
    ;                           /* find end of the destination list */
  end->succ = src;              /* append the source list */
  ((DLLE*)src)->pred = end;     /* at the end of the destination */
  return dst;                   /* return the destination list */
}  /* l_append() */

/*--------------------------------------------------------------------*/

static void* _merge (void *in1, void *in2, CMPFN cmpfn, void *data)
{                               /* --- merge two sorted lists */
  DLLE *dst, **end;             /* to create the output list */

  assert(in1 && in2 && cmpfn);  /* check the function arguments */
  end = &dst;                   /* start output list (merged input) */
  while (1) {                   /* source lists merge loop */
    if (cmpfn(in1, in2, data) < 0) { /* if element in 1st is smaller, */
      *end = in1;               /* move element to the output list */
      end  = &(((DLLE*)in1)->succ);
      in1  = *end;              /* remove element from the input list */
      if (!in1) break; }        /* if the list gets empty, abort loop */
    else {                      /* if 2nd list's element is smaller, */
      *end = in2;               /* move element to the output list */
      end  = &(((DLLE*)in2)->succ);
      in2  = *end;              /* remove element from the input list */
      if (!in2) break;          /* if the list gets empty, abort loop */
    }                           /* (merge input lists into one) */
  }
  if (in1) *end = in1;          /* append remaining elements */
  else     *end = in2;          /* to the output list */
  return dst;                   /* return the created output list */
}  /* _merge() */

/*--------------------------------------------------------------------*/

void* l_merge (void *in1, void *in2, CMPFN cmpfn, void *data)
{                               /* --- merge two sorted lists */
  DLLE *le;                     /* to traverse the merged list */

  assert(cmpfn);                /* check the function arguments */
  if (!in1) return in2;         /* if one of the lists is empty, */
  if (!in2) return in1;         /* simply return the other list */
  in1 = le = _merge(in1, in2, cmpfn, data);
  le->pred = NULL;              /* merge the two lists */
  for ( ; le->succ; le = le->succ)
    le->succ->pred = le;        /* set the predecessor pointers */
  return in1;                   /* return the created output list */
}  /* l_merge() */

/*--------------------------------------------------------------------*/

void* l_sort (void *list, CMPFN cmpfn, void *data)
{                               /* --- sort a list with mergesort */
  DLLE *src, *dst;              /* list of source/destination lists */
  DLLE **end;                   /* end of list of destination lists */

  if (!list) return list;       /* check for an empty list */
  for (src = list; src->succ; ) {
    dst = src; src = src->succ; /* traverse the list and split it */
    dst->succ = NULL;           /* into a list (abused pred ptr.) of */ 
  }                             /* single element lists (succ ptr.) */
  while (src->pred) {           /* while more than one source list */
    end = &dst;                 /* start list of destination lists */
    do {                        /* list merge loop */
      if (!src->pred) {         /* if there is only one source list, */
        *end = src; src = NULL; }    /* simply copy it to the output */
      else {                    /* if there are two source lists */
        *end = _merge(src, src->pred, cmpfn, data);
        src = src->pred->pred;  /* merge the two source lists and */
      }                         /* remove them from the source lists */
      end = &(*end)->pred;      /* go to the next output list */
    } while (src);              /* while there is another source list */
    *end = NULL;                /* terminate the destination list */
    src  = dst;                 /* transfer  the destination list */
  }                             /* to the source list and start over */
  for (src->pred = NULL; src->succ; src = src->succ)
    src->succ->pred = src;      /* set the predecessor pointers */
  return dst;                   /* return a pointer to the first */
}  /* l_sort() */               /* element of the sorted list */

/*--------------------------------------------------------------------*/
#ifdef LISTS_MAIN

typedef struct _strle {         /* --- string list element --- */
  struct _strle *succ;          /* pointer to successor */
  struct _strle *pred;          /* pointer to predecessor */
  char          *s;             /* string data of list element */
} STRLE;                        /* (string list element) */

/*--------------------------------------------------------------------*/

static int lexcmp (const void *p1, const void *p2, void *data)
{                               /* --- compare lexicographically */
  const char *s1 = ((const STRLE*)p1)->s;  /* type the two pointers */
  const char *s2 = ((const STRLE*)p2)->s;  /* to strings, */
  while (1) {                   /* then traverse the strings */
    if (*s1 <  *s2)  return -1; /* if one string is smaller than */
    if (*s1 >  *s2)  return +1; /* the other, return the result */
    if (*s1 == '\0') return  0; /* if at string end, return 'equal' */
    s1++; s2++;                 /* go to the next character */
  }
}  /* lexcmp() */

/*--------------------------------------------------------------------*/

static int numcmp (const void *p1, const void *p2, void *data)
{                               /* --- compare strings numerically */
  double d1 = strtod(((const STRLE*)p1)->s, NULL);
  double d2 = strtod(((const STRLE*)p2)->s, NULL);
  if (d1 < d2) return -1;       /* convert to numbers and */
  if (d1 > d2) return +1;       /* compare numerically */
  return lexcmp(p1, p2, NULL);  /* if the numbers are equal, */
}  /* numcmp() */               /* compare strings lexicographically */

/*--------------------------------------------------------------------*/

int main (int argc, char *argv[])
{                               /* --- sort program arguments */
  int   i, n;                   /* loop variables */
  int   numeric = 0;            /* flag for numeric comparison */
  char  *s;                     /* to traverse the arguments */
  STRLE *list, *buf;            /* to create the argument list */
  STRLE *le;                    /* to traverse the list elements */

  if (argc < 2) {               /* if no arguments are given */
    printf("usage: %s [arg [arg ...]]\n", argv[0]);
    printf("sort the list of program arguments\n");
    return 0;                   /* print a usage message */
  }                             /* and abort the program */
  for (i = n = 0; ++i < argc; ) {
    s = argv[i];                /* traverse the arguments */
    if (*s != '-') { argv[n++] = s; continue; }
    s++;                        /* store the arguments to sort */
    while (*s) {                /* traverse the options */
      switch (*s++) {           /* evaluate the options */
        case 'n': numeric = -1; break;
        default : printf("unknown option -%c\n", *--s); return -1;
      }                         /* set the option variables */
    }                           /* and check for known options */
  }
  list = buf = le = (STRLE*)malloc(n *sizeof(STRLE));
  if (!list) { printf("not enough memory\n"); return -1; }
  for (i = 0; i < n; i++) {     /* allocate the list elements and */
    le = list +i;               /* traverse the program arguments */
    le->s    = argv[i];         /* store the program argument */
    le->succ = le+1;            /* link each list element */
    le->pred = le-1;            /* with its successor */
  }                             /* and  its predecessor */
  le  ->succ = NULL;            /* terminate the list */
  list->pred = NULL;            /* at both ends */
  list = l_sort(list, (numeric) ? numcmp : lexcmp, NULL);
                                /* sort the program arguments */
  for (le = list; le; le = le->succ) { /* and then print them */
    fputs(le->s, stdout); fputc('\n', stdout); }
  return 0;                     /* return 'ok' */
}  /* main() */

#endif
