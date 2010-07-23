#-----------------------------------------------------------------------
# File    : table.mak
# Contents: build table management modules and utility programs
# Author  : Christian Borgelt
# History : 26.01.2003 file created
#           22.07.2003 program tnorm added
#           20.07.2006 adapted to Visual Studio 8
#-----------------------------------------------------------------------
CC       = cl.exe
LD       = link.exe
DEFS     = /D WIN32 /D NDEBUG /D _CONSOLE /D _MBCS \
           /D _CRT_SECURE_NO_DEPRECATE
CFLAGS   = /nologo /W3 /EHsc /O2 $(DEFS) /FD /c
LDFLAGS  = /nologo /subsystem:console /incremental:no /machine:X86
INC      = /I $(UTILDIR)

THISDIR  = ..\..\table\src
UTILDIR  = ..\..\util\src
HDRS     = $(UTILDIR)\arrays.h   $(UTILDIR)\tabscan.h \
           $(UTILDIR)\scan.h     attset.h
OBJS     = $(UTILDIR)\arrays.obj $(UTILDIR)\tabscan.obj \
           $(UTILDIR)\scform.obj attset1.obj attset2.obj
OBJS2    = $(UTILDIR)/arrays.obj $(UTILDIR)/tabscan.obj \
           $(UTILDIR)/scan.obj   $(UTILDIR)/parse.obj \
           attset1.obj attset2.obj $(ADDOBJ)
DOM_O    = $(OBJS) io.obj dom.obj
TMERGE_O = $(OBJS) io.obj tmerge.obj
TSPLIT_O = $(OBJS) table1.obj io_tab.obj tsplit.obj
TJOIN_O  = $(OBJS) table1.obj io_tab.obj tjoin.obj
TBAL_O   = $(OBJS) table1.obj io_tab.obj tbal.obj
TNORM_O  = $(OBJS) table1.obj io_tab.obj tnorm.obj
T1INN_O  = $(OBJS2) attset3.obj attmap.obj io.obj t1inn.obj
OPC_O    = $(OBJS) table1.obj table2.obj io_tab.obj opc.obj
XMAT_O   = $(UTILDIR)\symtab.obj $(UTILDIR)\tabscan.obj xmat.obj
INULLS_O = $(OBJS) table1.obj io_tab.obj inulls.obj
SKEL1_O  = $(OBJS) table1.obj io_tab.obj skel1.obj
SKEL2_O  = $(OBJS2) attset3.obj table1.obj io_tab.obj skel2.obj
PRGS     = dom.exe opc.exe tmerge.exe tsplit.exe tjoin.exe tbal.exe \
           tnorm.exe t1inn.exe xmat.exe inulls.exe

#-----------------------------------------------------------------------
# Build Programs
#-----------------------------------------------------------------------
all:        $(PRGS)

dom.exe:    $(DOM_O) table.mak
	$(LD) $(LDFLAGS) $(DOM_O) $(LIBS) /out:$@

opc.exe:    $(OPC_O) table.mak
	$(LD) $(LDFLAGS) $(OPC_O) $(LIBS) /out:$@

tmerge.exe: $(TMERGE_O) table.mak
	$(LD) $(LDFLAGS) $(TMERGE_O) $(LIBS) /out:$@

tsplit.exe: $(TSPLIT_O) table.mak
	$(LD) $(LDFLAGS) $(TSPLIT_O) $(LIBS) /out:$@

tjoin.exe:  $(TJOIN_O) table.mak
	$(LD) $(LDFLAGS) $(TJOIN_O) $(LIBS) /out:$@

tbal.exe:   $(TBAL_O) table.mak
	$(LD) $(LDFLAGS) $(TBAL_O) $(LIBS) /out:$@

tnorm.exe:  $(TNORM_O) table.mak
	$(LD) $(LDFLAGS) $(TNORM_O) $(LIBS) /out:$@

t1inn.exe:  $(T1INN_O) table.mak
	$(LD) $(LDFLAGS) $(T1INN_O) $(LIBS) /out:$@

xmat.exe:   $(XMAT_O) table.mak
	$(LD) $(LDFLAGS) $(XMAT_O) $(LIBS) /out:$@

inulls.exe: $(INULLS_O) table.mak
	$(LD) $(LDFLAGS) $(INULLS_O) $(LIBS) /out:$@

skel1.exe:  $(SKEL1_O) table.mak
	$(LD) $(LDFLAGS) $(SKEL1_O) $(LIBS) /out:$@

skel2.exe:  $(SKEL2_O) table.mak
	$(LD) $(LDFLAGS) $(SKEL2_O) $(LIBS) /out:$@

#-----------------------------------------------------------------------
# Main Programs
#-----------------------------------------------------------------------
dom.obj:      $(HDRS) io.h dom.c table.mak
	$(CC) $(CFLAGS) $(INC) dom.c /Fo$@

opc.obj:      $(HDRS) table.h io.h opc.c table.mak
	$(CC) $(CFLAGS) $(INC) opc.c /Fo$@

tmerge.obj:   $(HDRS) io.h tmerge.c table.mak
	$(CC) $(CFLAGS) $(INC) tmerge.c /Fo$@

tsplit.obj:   $(HDRS) table.h io.h tsplit.c table.mak
	$(CC) $(CFLAGS) $(INC) tsplit.c /Fo$@

tjoin.obj:    $(HDRS) table.h io.h tjoin.c table.mak
	$(CC) $(CFLAGS) $(INC) tjoin.c /Fo$@

tbal.obj:     $(HDRS) table.h io.h tbal.c table.mak
	$(CC) $(CFLAGS) $(INC) tbal.c /Fo$@

tnorm.obj:    $(HDRS) table.h io.h tnorm.c table.mak
	$(CC) $(CFLAGS) $(INC) tnorm.c /Fo$@

t1inn.obj:    $(HDRS) table.h io.h t1inn.c table.mak
	$(CC) $(CFLAGS) $(INC) t1inn.c /Fo$@

xmat.obj:     $(UTILDIR)\symtab.h $(UTILDIR)\tabscan.h xmat.c table.mak
	$(CC) $(CFLAGS) $(INC) xmat.c /Fo$@

inulls.obj:   $(HDRS) table.h io.h inulls.c table.mak
	$(CC) $(CFLAGS) $(INC) inulls.c /Fo$@

skel1.obj:    $(HDRS) table.h io.h skel1.c table.mak
	$(CC) $(CFLAGS) $(INC) skel1.c /Fo$@

skel2.obj:    $(HDRS) table.h io.h skel2.c table.mak
	$(CC) $(CFLAGS) $(INC) skel2.c /Fo$@

#-----------------------------------------------------------------------
# Attribute Set Management
#-----------------------------------------------------------------------
attset1.obj:   attset.h $(UTILDIR)\arrays.h attset1.c table.mak
	$(CC) $(CFLAGS) $(INC) -DAS_RDWR attset1.c /Fo$@

attset2.obj:   attset.h $(UTILDIR)\arrays.h attset2.c table.mak
	$(CC) $(CFLAGS) $(INC) -DAS_RDWR attset2.c /Fo$@

attset3.obj:   attset.h $(UTILDIR)\arrays.h attset3.c table.mak
	$(CC) $(CFLAGS) $(INC) attset3.c /Fo$@

#-----------------------------------------------------------------------
# Attribute Map Management
#-----------------------------------------------------------------------
attmap.obj:    attmap.h attset.h $(UTILDIR)\arrays.h attmap.c table.mak
	$(CC) $(CFLAGS) $(INC) attmap.c /Fo$@

#-----------------------------------------------------------------------
# Table Management
#-----------------------------------------------------------------------
table1.obj:   table.h attset.h table1.c table.mak
	$(CC) $(CFLAGS) $(INC) table1.c /Fo$@

table2.obj:   table.h attset.h table2.c table.mak
	$(CC) $(CFLAGS) $(INC) table2.c /Fo$@

#-----------------------------------------------------------------------
# Utility Functions for Visualization Programs
#-----------------------------------------------------------------------
tab4vis.obj:  tab4vis.h table.h tab4vis.c table.mak
	$(CC) $(CFLAGS) $(INC) tab4vis.c /Fo$@

#-----------------------------------------------------------------------
# Input/Output Utility Functions
#-----------------------------------------------------------------------
io.obj:       io.h attset.h $(UTILDIR)\scan.h io.c table.mak
	$(CC) $(CFLAGS) $(INC) io.c /Fo$@

io_tab.obj:   io.h attset.h table.h io.c table.mak
	$(CC) $(CFLAGS) $(INC) /D TAB_RDWR io.c /Fo$@

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
$(UTILDIR)\scform.obj:
	cd $(UTILDIR)
	$(MAKE) /f util.mak scform.obj
	cd $(THISDIR)
$(UTILDIR)\scan.obj:
	cd $(UTILDIR)
	$(MAKE) /f util.mak scan.obj
	cd $(THISDIR)
$(UTILDIR)\parse.obj:
	cd $(UTILDIR)
	$(MAKE) /f util.mak parse.obj
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
	$(MAKE) /f table.mak localclean
	cd $(UTILDIR)
	$(MAKE) /f util.mak clean
	cd $(THISDIR)

localclean:
	-@erase /Q *~ *.obj *.idb *.pch $(PRGS) skel1 skel2
