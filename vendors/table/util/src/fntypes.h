/*----------------------------------------------------------------------
  File    : fntypes.h
  Contents: definition of some common function types
  Author  : Christian Borgelt
  History : 2008.08.11 file created
----------------------------------------------------------------------*/
#ifndef __FNTYPES__
#define __FNTYPES__

typedef void   OBJFN  (void *obj);
typedef int    CMPFN  (const void *p1, const void *p2, void *data);
typedef double RANDFN (void);

#endif
