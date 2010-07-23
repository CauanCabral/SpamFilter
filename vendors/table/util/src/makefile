#-----------------------------------------------------------------------
# File    : makefile    (directory: util)
# Contents: build utility modules
# Author  : Christian Borgelt
# History : 2000.11.04 file created from makefile in table directory
#           2003.06.05 module params added
#           2003.08.12 module nstats added
#           2004.12.10 module memsys added
#           2008.08.01 adapted to name changes of arrays and lists
#           2008.08.18 adapted to main functions of arrays and lists
#-----------------------------------------------------------------------
CC      = gcc
CFBASE  = -ansi -Wall -pedantic $(ADDFLAGS)
CFLAGS  = $(CFBASE) -DNDEBUG -O3
# CFLAGS  = $(CFBASE) -g
# CFLAGS  = $(CFBASE) -g -DSTORAGE $(ADDINC)
# ADDINC  = -I../../misc/src
INC      = -I. -I$(TABLEDIR)
PROGS    = sortargs listtest

#-----------------------------------------------------------------------
# Build Programs
#-----------------------------------------------------------------------
all:        $(PROGS)

sortargs:   sortargs.o makefile
	$(CC) $(LDFLAGS) $(LIBS) sortargs.o -o $@

listtest:   listtest.o makefile
	$(CC) $(LDFLAGS) $(LIBS) listtest.o -o $@

#-----------------------------------------------------------------------
# Programs
#-----------------------------------------------------------------------
sortargs.o: arrays.h fntypes.h
sortargs.o: arrays.c makefile
	$(CC) $(CFLAGS) -DARRAYS_MAIN -c arrays.c -o $@

listtest.o: lists.h fntypes.h
listtest.o: lists.c makefile
	$(CC) $(CFLAGS) -DLISTS_MAIN -c lists.c -o $@

#-----------------------------------------------------------------------
# Array Operations
#-----------------------------------------------------------------------
arrays.o:   arrays.h fntypes.h
arrays.o:   arrays.c makefile
	$(CC) $(CFLAGS) -c arrays.c -o $@

#-----------------------------------------------------------------------
# List Operations
#-----------------------------------------------------------------------
lists.o:    lists.h fntypes.h
lists.o:    lists.c makefile
	$(CC) $(CFLAGS) -c lists.c -o $@

#-----------------------------------------------------------------------
# Memory Management System for Objects of Equal Size
#-----------------------------------------------------------------------
memsys.o:   memsys.h
memsys.o:   memsys.c makefile
	$(CC) $(CFLAGS) -c memsys.c -o $@

#-----------------------------------------------------------------------
# Symbol Table Management
#-----------------------------------------------------------------------
symtab.o:   symtab.h fntypes.h
symtab.o:   symtab.c makefile
	$(CC) $(CFLAGS) -c symtab.c -o $@

nimap.o:    symtab.h fntypes.h arrays.h
nimap.o:    symtab.c makefile
	$(CC) $(CFLAGS) -DNIMAPFN -c symtab.c -o $@

#-----------------------------------------------------------------------
# Numerical Statistics Management
#-----------------------------------------------------------------------
nstats.o:   nstats.h
nstats.o:   nstats.c makefile
	$(CC) $(CFLAGS) -c nstats.c -o $@

nst_pars.o: nstats.h
nst_pars.o: nstats.c makefile
	$(CC) $(CFLAGS) -DNST_PARSE -c nstats.c -o $@

#-----------------------------------------------------------------------
# Table Scanner Management
#-----------------------------------------------------------------------
tabscan.o:  tabscan.h
tabscan.o:  tabscan.c makefile
	$(CC) $(CFLAGS) -c tabscan.c -o $@

#-----------------------------------------------------------------------
# Scanner
#-----------------------------------------------------------------------
scform.o:   scan.h
scform.o:   scan.c makefile
	$(CC) $(CFLAGS) -c scan.c -o $@

scan.o:     scan.h
scan.o:     scan.c makefile
	$(CC) $(CFLAGS) -DSC_SCAN -c scan.c -o $@

#-----------------------------------------------------------------------
# Parser Utilities
#-----------------------------------------------------------------------
parse.o:    parse.h
parse.o:    parse.c makefile
	$(CC) $(CFLAGS) -c parse.c -o $@

#-----------------------------------------------------------------------
# Command Line Parameter Retrieval
#-----------------------------------------------------------------------
params.o:   params.h
params.o:   params.c makefile
	$(CC) $(CFLAGS) -c params.c -o $@

#-----------------------------------------------------------------------
# Source Distribution Packages
#-----------------------------------------------------------------------
dist:
	cd ../..; rm -f util.zip util.tar.gz; \
        zip -rq util.zip    util/{src,doc}; \
        tar cfz util.tar.gz util/{src,doc}

#-----------------------------------------------------------------------
# Clean up
#-----------------------------------------------------------------------
clean:
	rm -f $(PROGS) *.o *~ *.flc core
