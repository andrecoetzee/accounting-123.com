for x in `grep -R ">R \$\w" * | cut -f 1 -d ':' | uniq`; do replace ">R \$\w" "\"\.CUR\.\"" -- $x; done
