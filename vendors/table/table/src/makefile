#-----------------------------------------------------------------------
# File    : makefile    (directory: table)
# Contents: build table management modules and utility programs
# Author  : Christian Borgelt
# History : 1995.??.?? file created
#           1997.08.26 makefile splitted into subdirectory makefiles
#           1998.01.04 table file scanner management added
#           1998.02.08 scanner transferred from parse makefile
#           1998.02.24 skeleton programs transferred from skel makefile
#           1998.02.25 programs tmerge and tsplit added
#           1999.02.05 program tjoin added
#           1999.02.13 program tbal added
#           1999.04.17 input/output utility functions added
#           2000.11.04 modules arrays, symtab, and tabscan made external
#           2001.06.24 modules attest and table split into two files
#           2001.11.08 module tab4vis added
#           2002.02.22 module attset3 added, as_rwp removed
#           2003.07.22 program tnorm added
#           2003.08.11 program t1inn added
#           2008.08.11 adapted to name change from vecops to arrays
#-----------------------------------------------------------------------
CC        = gcc
CFBASE    = -ansi -Wall -pedantic $(ADDFLAGS)
CFLAGS    = $(CFBASE) -DNDEBUG -O3
# CFLAGS    = $(CFBASE) -g
# CFLAGS    = $(CFBASE) -g $(ADDINC) -DSTORAGE
LDFLAGS   = 
INC       = -I$(UTILDIR)
LIBS      = -lm
#ADDINC    = -I../../misc/src
#ADDOBJ    = storage.o

UTILDIR   = ../../util/src
HDRS      = $(UTILDIR)/arrays.h $(UTILDIR)/tabscan.h \
            $(UTILDIR)/scan.h   attset.h
OBJS      = $(UTILDIR)/arrays.o $(UTILDIR)/tabscan.o \
            $(UTILDIR)/scform.o attset1.o attset2.o $(ADDOBJ)
OBJS2     = $(UTILDIR)/arrays.o $(UTILDIR)/tabscan.o \
            $(UTILDIR)/scan.o   $(UTILDIR)/parse.o \
            attset1.o attset2.o $(ADDOBJ)
DOM_O     = $(OBJS) io.o dom.o
TMERGE_O  = $(OBJS) io.o tmerge.o
TSPLIT_O  = $(OBJS) table1.o io_tab.o tsplit.o
TJOIN_O   = $(OBJS) table1.o io_tab.o tjoin.o
TBAL_O    = $(OBJS) table1.o io_tab.o tbal.o
TNORM_O   = $(OBJS) table1.o io_tab.o tnorm.o
T1INN_O   = $(OBJS2) attset3.o attmap.o io.o t1inn.o
OPC_O     = $(OBJS) table1.o table2.o io_tab.o opc.o
XMAT_O    = $(UTILDIR)/symtab.o $(UTILDIR)/tabscan.o xmat.o $(ADDOBJ)
INULLS_O  = $(OBJS) table1.o io_tab.o inulls.o
SKEL1_O   = $(OBJS) table1.o io_tab.o skel1.o
SKEL2_O   = $(OBJS2) attset3.o table1.o io_tab.o skel2.o $(ADDOBJ)
PRGS      = dom opc tmerge tsplit tjoin tbal tnorm t1inn xmat inulls

#-----------------------------------------------------------------------
# Build Programs
#-----------------------------------------------------------------------
all:        $(PRGS)

dom:        $(DOM_O) makefile
	$(CC) $(LDFLAGS) $(DOM_O) $(LIBS) -o $@

opc:        $(OPC_O) makefile
	$(CC) $(LDFLAGS) $(OPC_O) $(LIBS) -o $@

tmerge:     $(TMERGE_O) makefile
	$(CC) $(LDFLAGS) $(TMERGE_O) $(LIBS) -o $@

tsplit:     $(TSPLIT_O) makefile
	$(CC) $(LDFLAGS) $(TSPLIT_O) $(LIBS) -o $@

tjoin:      $(TJOIN_O) makefile
	$(CC) $(LDFLAGS) $(TJOIN_O) $(LIBS) -o $@

tbal:       $(TBAL_O) makefile
	$(CC) $(LDFLAGS) $(TBAL_O) $(LIBS) -o $@

tnorm:      $(TNORM_O) makefile
	$(CC) $(LDFLAGS) $(TNORM_O) $(LIBS) -o $@

t1inn:      $(T1INN_O) makefile
	$(CC) $(LDFLAGS) $(T1INN_O) $(LIBS) -o $@

xmat:       $(XMAT_O) makefile
	$(CC) $(LDFLAGS) $(XMAT_O) $(LIBS) -o $@

inulls:     $(INULLS_O) makefile
	$(CC) $(LDFLAGS) $(INULLS_O) $(LIBS) -o $@

skel1:      $(SKEL1_O) makefile
	$(CC) $(LDFLAGS) $(SKEL1_O) $(LIBS) -o $@

skel2:      $(SKEL2_O) makefile
	$(CC) $(LDFLAGS) $(SKEL2_O) $(LIBS) -o $@

#-----------------------------------------------------------------------
# Main Programs
#-----------------------------------------------------------------------
dom.o:      $(HDRS) io.h
dom.o:      dom.c makefile
	$(CC) $(CFLAGS) $(INC) -c dom.c -o $@

opc.o:      $(HDRS) table.h io.h
opc.o:      opc.c makefile
	$(CC) $(CFLAGS) $(INC) -c opc.c -o $@

tmerge.o:   $(HDRS) io.h
tmerge.o:   tmerge.c makefile
	$(CC) $(CFLAGS) $(INC) -c tmerge.c -o $@

tsplit.o:   $(HDRS) table.h io.h
tsplit.o:   tsplit.c makefile
	$(CC) $(CFLAGS) $(INC) -c tsplit.c -o $@

tjoin.o:    $(HDRS) table.h io.h
tjoin.o:    tjoin.c makefile
	$(CC) $(CFLAGS) $(INC) -c tjoin.c -o $@

tbal.o:     $(HDRS) table.h io.h
tbal.o:     tbal.c makefile
	$(CC) $(CFLAGS) $(INC) -c tbal.c -o $@

tnorm.o:    $(HDRS) table.h io.h
tnorm.o:    tnorm.c makefile
	$(CC) $(CFLAGS) $(INC) -c tnorm.c -o $@

t1inn.o:    $(HDRS) io.h attmap.h
t1inn.o:    t1inn.c makefile
	$(CC) $(CFLAGS) $(INC) -c t1inn.c -o $@

xmat.o:     $(UTILDIR)/symtab.h $(UTILDIR)/tabscan.h
xmat.o:     xmat.c makefile
	$(CC) $(CFLAGS) $(INC) -c xmat.c -o $@

inulls.o:   $(HDRS) table.h io.h
inulls.o:   inulls.c makefile
	$(CC) $(CFLAGS) $(INC) -c inulls.c -o $@

skel1.o:    $(HDRS) table.h io.h
skel1.o:    skel1.c makefile
	$(CC) $(CFLAGS) $(INC) -c skel1.c -o $@

skel2.o:    $(HDRS) table.h io.h
skel2.o:    skel2.c makefile
	$(CC) $(CFLAGS) $(INC) -c skel2.c -o $@

#-----------------------------------------------------------------------
# Attribute Set Management
#-----------------------------------------------------------------------
attset1.o:   attset.h $(UTILDIR)/arrays.h
attset1.o:   attset1.c makefile
	$(CC) $(CFLAGS) $(INC) -DAS_RDWR -c attset1.c -o $@

attset2.o:   attset.h $(UTILDIR)/arrays.h
attset2.o:   attset2.c makefile
	$(CC) $(CFLAGS) $(INC) -DAS_RDWR -c attset2.c -o $@

attset3.o:   attset.h $(UTILDIR)/arrays.h
attset3.o:   attset3.c makefile
	$(CC) $(CFLAGS) $(INC) -c attset3.c -o $@

#-----------------------------------------------------------------------
# Attribute Map Management
#-----------------------------------------------------------------------
attmap.o:    attmap.h attset.h
attmap.o:    attmap.c makefile
	$(CC) $(CFLAGS) $(INC) -c attmap.c -o $@

#-----------------------------------------------------------------------
# Table Management
#-----------------------------------------------------------------------
table1.o:   table.h attset.h
table1.o:   table1.c makefile
	$(CC) $(CFLAGS) $(INC) -c table1.c -o $@

table2.o:   table.h attset.h
table2.o:   table2.c makefile
	$(CC) $(CFLAGS) $(INC) -c table2.c -o $@

#-----------------------------------------------------------------------
# Utility Functions for Visualization Programs
#-----------------------------------------------------------------------
tab4vis.o:  tab4vis.h table.h
tab4vis.o:  tab4vis.c makefile
	$(CC) $(CFLAGS) $(INC) -c tab4vis.c -o $@

#-----------------------------------------------------------------------
# Input/Output Utility Functions
#-----------------------------------------------------------------------
io.o:       io.h attset.h $(UTILDIR)/scan.h
io.o:       io.c makefile
	$(CC) $(CFLAGS) $(INC) -c io.c -o $@

io_tab.o:   io.h attset.h table.h
io_tab.o:   io.c makefile
	$(CC) $(CFLAGS) $(INC) -DTAB_RDWR -c io.c -o $@

#-----------------------------------------------------------------------
# External Modules
#-----------------------------------------------------------------------
$(UTILDIR)/arrays.o:
	cd $(UTILDIR); $(MAKE) arrays.o  ADDFLAGS=$(ADDFLAGS)
$(UTILDIR)/symtab.o:
	cd $(UTILDIR); $(MAKE) symtab.o  ADDFLAGS=$(ADDFLAGS)
$(UTILDIR)/tabscan.o:
	cd $(UTILDIR); $(MAKE) tabscan.o ADDFLAGS=$(ADDFLAGS)
$(UTILDIR)/scform.o:
	cd $(UTILDIR); $(MAKE) scform.o  ADDFLAGS=$(ADDFLAGS)
$(UTILDIR)/scan.o:
	cd $(UTILDIR); $(MAKE) scan.o    ADDFLAGS=$(ADDFLAGS)

#-----------------------------------------------------------------------
# Storage Debugging
#-----------------------------------------------------------------------
storage.o:  ../../misc/src/storage.h
storage.o:  ../../misc/src/storage.c
	$(CC) $(CFLAGS) -c ../../misc/src/storage.c -o $@

#-----------------------------------------------------------------------
# Source Distribution Packages
#-----------------------------------------------------------------------
dist:
	cd ../..; rm -f table.zip table.tar.gz; \
        zip -rq table.zip    table/{src,ex,doc} util/{src,doc}; \
        tar cfz table.tar.gz table/{src,ex,doc} util/{src,doc}

#-----------------------------------------------------------------------
# Installation
#-----------------------------------------------------------------------
install:
	cp $(PRGS) $(HOME)/bin

#-----------------------------------------------------------------------
# Clean up
#-----------------------------------------------------------------------
clean:
	rm -f *.o *~ *.flc core $(PRGS) skel1 skel2
	cd $(UTILDIR); $(MAKE) clean

localclean:
	rm -f *.o *~ *.flc core $(PRGS) skel1 skel2
