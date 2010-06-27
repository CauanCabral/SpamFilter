#-----------------------------------------------------------------------
# File    : bayes.mak
# Contents: commands to build naive and full Bayes classifier programs
# Author  : Christian Borgelt
# History : 26.01.2003 file created
#           26.04.2003 program bcdb added
#           20.07.2006 adapted to Visual Studio 8
#-----------------------------------------------------------------------
CC       = cl.exe
LD       = link.exe
DEFS     = /D WIN32 /D NDEBUG /D _CONSOLE /D _MBCS \
           /D _CRT_SECURE_NO_DEPRECATE
CFLAGS   = /nologo /W3 /EHsc /O2 $(DEFS) /FD /c
LDFLAGS  = /nologo /subsystem:console /incremental:no /machine:X86
INC      = /I $(UTILDIR) /I $(TABLEDIR)

THISDIR  = ..\..\bayes\src
UTILDIR  = ..\..\util\src
TABLEDIR = ..\..\table\src
HDRS     = $(UTILDIR)\arrays.h     $(UTILDIR)\scan.h \
           $(TABLEDIR)\attset.h    $(TABLEDIR)\table.h
BCHDRS   = $(HDRS) $(UTILDIR)\tabscan.h $(UTILDIR)\parse.h \
           $(TABLEDIR)\io.h mvnorm.h fbayes.h nbayes.h
OBJS     = $(UTILDIR)\arrays.obj   $(UTILDIR)\tabscan.obj \
           $(UTILDIR)\scan.obj     $(UTILDIR)\parse.obj \
           $(TABLEDIR)\attset1.obj $(TABLEDIR)\attset2.obj \
           $(TABLEDIR)\attset3.obj
BCI_O    = $(OBJS) $(TABLEDIR)\io_tab.obj $(TABLEDIR)\table1.obj \
           mvnorm.obj fbc_ind.obj nbc_ind.obj bci.obj
BCX_O    = $(OBJS) $(TABLEDIR)\io.obj \
           mvn_pars.obj fbc_exec.obj nbc_exec.obj bcx.obj
BCDB_O   = $(OBJS) mvn_pars.obj fbc_exec.obj nbc_exec.obj bcdb.obj
CORR_O   = $(UTILDIR)\symtab.obj $(UTILDIR)\tabscan.obj \
           mvnorm.obj corr.obj
PRGS     = bci.exe bcx.exe bcdb.exe corr.exe

#-----------------------------------------------------------------------
# Build Programs
#-----------------------------------------------------------------------
all:        $(PRGS)

bci.exe:    $(BCI_O) bayes.mak
	$(LD) $(LDFLAGS) $(BCI_O) $(LIBS) /out:$@

bcx.exe:    $(BCX_O) bayes.mak
	$(LD) $(LDFLAGS) $(BCX_O) $(LIBS) /out:$@

bcdb.exe:   $(BCDB_O) bayes.mak
	$(LD) $(LDFLAGS) $(BCDB_O) $(LIBS) /out:$@

corr.exe:   $(CORR_O) bayes.mak
	$(LD) $(LDFLAGS) $(CORR_O) $(LIBS) /out:$@

#-----------------------------------------------------------------------
# Main Programs
#-----------------------------------------------------------------------
bci.obj:    $(BCHDRS) bci.c bayes.mak
	$(CC) $(CFLAGS) $(INC) bci.c /Fo$@

bcx.obj:    $(BCHDRS) bcx.c bayes.mak
	$(CC) $(CFLAGS) $(INC) bcx.c /Fo$@

bcdb.obj:   $(BCHDRS) bcdb.c bayes.mak
	$(CC) $(CFLAGS) $(INC) bcdb.c /Fo$@

corr.obj:   $(UTILDIR)\symtab.h $(UTILDIR)\tabscan.h \
            mvnorm.h corr.c bayes.mak
	$(CC) $(CFLAGS) $(INC) corr.c /Fo$@

#-----------------------------------------------------------------------
# Naive Bayes Classifier Management
#-----------------------------------------------------------------------
nbc_ind.obj:  $(HDRS) nbayes.h nbayes.c bayes.mak
	$(CC) $(CFLAGS) $(INC) /D NBC_INDUCE nbayes.c /Fo$@

nbc_exec.obj: $(HDRS) nbayes.h nbayes.c bayes.mak
	$(CC) $(CFLAGS) $(INC) /D NBC_PARSE nbayes.c /Fo$@

#-----------------------------------------------------------------------
# Full Bayes Classifier Management
#-----------------------------------------------------------------------
fbc_ind.obj:  $(HDRS) mvnorm.h fbayes.h fbayes.c bayes.mak
	$(CC) $(CFLAGS) $(INC) /D FBC_INDUCE fbayes.c /Fo$@

fbc_exec.obj: $(HDRS) mvnorm.h fbayes.h fbayes.c bayes.mak
	$(CC) $(CFLAGS) $(INC) /D FBC_PARSE fbayes.c /Fo$@

#-----------------------------------------------------------------------
# Multivariate Normal Distribution Management
#-----------------------------------------------------------------------
mvnorm.obj:   $(UTILDIR)\scan.h mvnorm.h mvnorm.c bayes.mak
	$(CC) $(CFLAGS) $(INC) mvnorm.c /Fo$@

mvn_pars.obj: $(UTILDIR)\scan.h mvnorm.h mvnorm.c bayes.mak
	$(CC) $(CFLAGS) $(INC) /D MVN_PARSE mvnorm.c /Fo$@

#-----------------------------------------------------------------------
# External Modules
#-----------------------------------------------------------------------
$(UTILDIR)\arrays.obj:
	cd $(UTILDIR)
	$(MAKE) /f util.mak arrays.obj
	cd $(THISDIR)
$(UTILDIR)\symtab.obj:
	cd $(UTILDIR)
	$(MAKE) /f util.mak symtab.obj
	cd $(THISDIR)
$(UTILDIR)\tabscan.obj:
	cd $(UTILDIR)
	$(MAKE) /f util.mak tabscan.obj
	cd $(THISDIR)
$(UTILDIR)\scan.obj:
	cd $(UTILDIR)
	$(MAKE) /f util.mak scan.obj
	cd $(THISDIR)
$(UTILDIR)\parse.obj:
	cd $(UTILDIR)
	$(MAKE) /f util.mak parse.obj
	cd $(THISDIR)
$(TABLEDIR)\attset1.obj:
	cd $(TABLEDIR)
	$(MAKE) /f table.mak attset1.obj
	cd $(THISDIR)
$(TABLEDIR)\attset2.obj:
	cd $(TABLEDIR)
	$(MAKE) /f table.mak attset2.obj
	cd $(THISDIR)
$(TABLEDIR)\attset3.obj:
	cd $(TABLEDIR)
	$(MAKE) /f table.mak attset3.obj
	cd $(THISDIR)
$(TABLEDIR)\table1.obj:
	cd $(TABLEDIR)
	$(MAKE) /f table.mak table1.obj
	cd $(THISDIR)
$(TABLEDIR)\io.obj:
	cd $(TABLEDIR)
	$(MAKE) /f table.mak io.obj
	cd $(THISDIR)
$(TABLEDIR)\io_tab.obj:
	cd $(TABLEDIR)
	$(MAKE) /f table.mak io_tab.obj
	cd $(THISDIR)

#-----------------------------------------------------------------------
# Install
#-----------------------------------------------------------------------
install:
	-@copy *.exe c:\home\bin

#-----------------------------------------------------------------------
# Clean up
#-----------------------------------------------------------------------
clean:
	$(MAKE) /f bayes.mak localclean
	cd $(UTILDIR)
	$(MAKE) /f util.mak clean
	cd $(TABLEDIR)
	$(MAKE) /f table.mak localclean
	cd $(THISDIR)

localclean:
	-@erase /Q *~ *.obj *.idb *.pch $(PRGS)
