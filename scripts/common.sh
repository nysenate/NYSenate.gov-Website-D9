#
# Exit with a message if the previous function returned an error.
#
# Usage:
#
#   aborterr "Description of what went wrong"
#
function aborterr() {
  if [ $? != 0 ]
  then
    echo "$1" >&2
    exit 1
  fi
}

#
# Check the result of the last operation, and either print progress or call aborterr
#
# Usage:
#
#   check "Something the script did" "Message to display if it did not work"
#
function check() {
  aborterr "$2"
  echo "$1"
}

# http://stackoverflow.com/questions/5014632/how-can-i-parse-a-yaml-file-from-a-linux-shell-script
function parse_yaml {
   local prefix=$2
   local s='[[:space:]]*' w='[a-zA-Z0-9_]*' fs=$(echo @|tr @ '\034')
   sed -ne "s|^\($s\):|\1|" \
        -e 's|\\||g' \
        -e "s|^\($s\)\($w\)$s:$s[\"']\(.*\)[\"']$s\$|\1$fs\2$fs\3|p" \
        -e "s|^\($s\)\($w\)$s:$s\(.*\)$s\$|\1$fs\2$fs\3|p"  $1 |
   awk -F$fs '{
      indent = length($1)/2;
      vname[indent] = $2;
      for (i in vname) {if (i > indent) {delete vname[i]}}
      if (length($3) > 0) {
         vn=""; for (i=0; i<indent; i++) {vn=(vn)(vname[i])("_")}
         printf("%s%s%s=\"%s\"\n", "'$prefix'",vn, $2, $3);
      }
   }'
}

# Read DrupalVM configuration
eval $(parse_yaml config/config.yml "DRUPALVM_")
