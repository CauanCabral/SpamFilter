#-----------------------------------------------------------------------
# File    : makefile    (directory: bayes)
# Contents: commands to build naive and full Bayes classifier programs
# Author  : Christian Borgelt
# History : 1998.08.12 file created
#           1999.23.03 storage debugging module added
#           1999.17.04 input/output utility functions added
#           2000.10.11 program corr moved here from table directory
#           2000.26.11 module mvnorm added
#           2002.22.01 adapted to changed table makefile
#           2003.26.04 program bcdb added
#           2004.12.08 adapted to new module parse
#           2008.08.11 adapted to name change from vecops to arrays
#-----------------------------------------------------------------------
CC        = gcc
CFBASE    = -ansi -Wall -pedantic $(ADDFLAGS)
CFLAGS    = $(CFBASE) -DNDEBUG -O3
# CFLAGS    = $(CFBASE) -g
# CFLAGS    = $(CFBASE) -g $(ADDINC) -DSTORAGE
INC       = -I$(UTILDIR) -I$(TABLEDIR)
LIBS      = -lm
# ADDINC    = -I../../misc/src
# ADDOBJ    = storage.o

UTILDIR   = ../../util/src
TABLEDIR  = ../../table/src
HDRS      = $(UTILDIR)/arrays.h   $(UTILDIR)/scan.h \
            $(TABLEDIR)/attset.h  $(TABLEDIR)/table.h
BCHDRS    = $(HDRS) $(UTILDIR)/tabscan.h $(UTILDIR)/parse.h \
            $(TABLEDIR)/io.h mvnorm.h fbayes.h nbayes.h
OBJS      = $(UTILDIR)/arrays.o   $(UTILDIR)/tabscan.o \
            $(UTILDIR)/scan.o     $(UTILDIR)/parse.o \
            $(TABLEDIR)/attset1.o $(TABLEDIR)/attset2.o \
            $(TABLEDIR)/attset3.o $(ADDOBJ)
BCI_O     = $(OBJS) $(TABLEDIR)/io_tab.o $(TABLEDIR)/table1.o \
            mvnorm.o fbc_ind.o nbc_ind.o bci.o
BCX_O     = $(OBJS) $(TABLEDIR)/io.o \
            mvn_pars.o fbc_exec.o nbc_exec.o bcx.o
BCDB_O    = $(OBJS) mvn_pars.o fbc_exec.o nbc_exec.o bcdb.o
CORR_O    = $(UTILDIR)/symtab.o $(UTILDIR)/tabscan.o \
            mvnorm.o corr.o $(ADDOBJ)
PRGS      = bci bcx bcdb corr

#-----------------------------------------------------------------------
# Build Programs
#-----------------------------------------------------------------------
all:        $(PRGS)

bci:        $(BCI_O) makefile
	$(CC) $(LDFLAGS) $(BCI_O) $(LIBS) -o $@

bcx:        $(BCX_O) makefile
	$(CC) $(LDFLAGS) $(BCX_O) $(LIBS) -o $@

bcdb:       $(BCDB_O) makefile
	$(CC) $(LDFLAGS) $(BCDB_O) $(LIBS) -o $@

corr:       $(CORR_O) makefile
	$(CC) $(LDFLAGS) $(CORR_O) $(LIBS) -o $@

#-----------------------------------------------------------------------
# Main Programs
#-----------------------------------------------------------------------
bci.o:      $(BCHDRS)
bci.o:      bci.c makefile
	$(CC) $(CFLAGS) $(INC) -c bci.c -o $@

bcx.o:      $(BCHDRS)
bcx.o:      bcx.c makefile
	$(CC) $(CFLAGS) $(INC) -c bcx.c -o $@

bcdb.o:     $(BCHDRS)
bcdb.o:     bcdb.c makefile
	$(CC) $(CFLAGS) $(INC) -DDRAND48 -c bcdb.c -o $@

corr.o:     mvnorm.h $(UTILDIR)/symtab.h $(UTILDIR)/tabscan.h
corr.o:     corr.c makefile
	$(CC) $(CFLAGS) $(INC) -c corr.c -o $@

#-----------------------------------------------------------------------
# Naive Bayes Classifier Management
#-----------------------------------------------------------------------
nbc_ind.o:  nbayes.h $(HDRS)
nbc_ind.o:  nbayes.c makefile
	$(CC) $(CFLAGS) $(INC) -DNBC_INDUCE -c nbayes.c -o $@

nbc_exec.o: nbayes.h $(HDRS)
nbc_exec.o: nbayes.c makefile
	$(CC) $(CFLAGS) $(INC) -DNBC_PARSE -c nbayes.c -o $@

#-----------------------------------------------------------------------
# Full Bayes Classifier Management
#-----------------------------------------------------------------------
fbc_ind.o:  fbayes.h mvnorm.h $(HDRS)
fbc_ind.o:  fbayes.c makefile
	$(CC) $(CFLAGS) $(INC) -DFBC_INDUCE -c fbayes.c -o $@

fbc_exec.o: fbayes.h mvnorm.h $(HDRS)
fbc_exec.o: fbayes.c makefile
	$(CC) $(CFLAGS) $(INC) -DFBC_PARSE -c fbayes.c -o $@

#-----------------------------------------------------------------------
# Multivariate Normal Distribution Management
#-----------------------------------------------------------------------
mvnorm.o:   mvnorm.h $(UTILDIR)/scan.h
mvnorm.o:   mvnorm.c makefile
	$(CC) $(CFLAGS) $(INC) -c mvnorm.c -o $@

mvn_pars.o: mvnorm.h $(UTILDIR)/scan.h
mvn_pars.o: mvnorm.c makefile
	$(CC) $(CFLAGS) $(INC) -DMVN_PARSE -c mvnorm.c -o $@

#-----------------------------------------------------------------------
# Storage Debugging
#-----------------------------------------------------------------------
storage.o:  ../../misc/src/storage.h
storage.o:  ../../misc/src/storage.c
	$(CC) $(CFLAGS) -c ../../misc/src/storage.c -o $@

#-----------------------------------------------------------------------
# External Modules
#-----------------------------------------------------------------------
$(UTILDIR)/arrays.o:
	cd $(UTILDIR);  $(MAKE) arrays.o  ADDFLAGS=$(ADDFLAGS)
$(UTILDIR)/symtab.o:
	cd $(UTILDIR);  $(MAKE) symtab.o  ADDFLAGS=$(ADDFLAGS)
$(UTILDIR)/tabscan.o:
	cd $(UTILDIR);  $(MAKE) tabscan.o ADDFLAGS=$(ADDFLAGS)
$(UTILDIR)/scan.o:
	cd $(UTILDIR);  $(MAKE) scan.o    ADDFLAGS=$(ADDFLAGS)
$(UTILDIR)/parse.o:
	cd $(UTILDIR);  $(MAKE) parse.o   ADDFLAGS=$(ADDFLAGS)
$(TABLEDIR)/attset1.o:
	cd $(TABLEDIR); $(MAKE) attset1.o ADDFLAGS=$(ADDFLAGS)
$(TABLEDIR)/attset2.o:
	cd $(TABLEDIR); $(MAKE) attset2.o ADDFLAGS=$(ADDFLAGS)
$(TABLEDIR)/attset3.o:
	cd $(TABLEDIR); $(MAKE) attset3.o ADDFLAGS=$(ADDFLAGS)
$(TABLEDIR)/table1.o:
	cd $(TABLEDIR); $(MAKE) table1.o  ADDFLAGS=$(ADDFLAGS)
$(TABLEDIR)/io.o:
	cd $(TABLEDIR); $(MAKE) io.o      ADDFLAGS=$(ADDFLAGS)
$(TABLEDIR)/io_tab.o:
	cd $(TABLEDIR); $(MAKE) io_tab.o  ADDFLAGS=$(ADDFLAGS)

#-----------------------------------------------------------------------
# Source Distribution Packages
#-----------------------------------------------------------------------
dist:
	cd ../..; rm -f bayes.zip bayes.tar.gz; \
        zip -rq bayes.zip    bayes/{src,ex,doc}; \
        tar cfz bayes.tar.gz bayes/{src,ex,doc}

#-----------------------------------------------------------------------
# Installation
#-----------------------------------------------------------------------
install:
	cp $(PRGS) $(HOME)/bin

#-----------------------------------------------------------------------
# Clean up
#-----------------------------------------------------------------------
clean:
	rm -f *.o *~ *.flc core $(PRGS)
	cd $(UTILDIR);  $(MAKE) clean
	cd $(TABLEDIR); $(MAKE) localclean

localclean:
	rm -f *.o *~ *.flc core $(PRGS)
