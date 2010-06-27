#!/bin/sh

if (( $# < 2 )); then
  echo "usage: xval.sh data target [folds]"
  echo "data    name of the data file (without extension)"
  echo "target  name of the target attribute"
  echo "folds   number of folds (default: 3)"
  exit
fi

data=$1
target=$2
folds=${3:-3}

tsplit -xc$target $data.tab -t$folds 2>/dev/null

rm -rf xval.tmp
for (( i = 0; i < folds; i++ )); do
  list=""
  for (( k = 0; k < folds; k++ )); do
    if (( k != i )); then list="$list $k.tab"; fi
  done
  tmerge $list - 2>/dev/null | \
    bci $data.dom - - 2>/dev/null | \
    bcx - $i.tab 2>&1 | \
    gawk '($2 ~ "error[(]s[)]") {
      printf("%s %s\n", $1, substr($3, 2, length($3)-3)) }' \
    >> xval.tmp
done

cnt=`wc -l $data.tab | gawk '{ print $1 }'`
gawk -v sum=$(( cnt -1 )) '{ cnt += $1; }
END { printf("%d error(s) (%.2f%%)\n", cnt, cnt/sum *100) }' xval.tmp
rm -rf $list $(( folds -1 )).tab xval.tmp
