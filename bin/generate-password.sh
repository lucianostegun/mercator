export DATE_TIME=$(date +"%Y-%m-%d %H:%M")

export HASH=$(php -r "echo hash('sha1', '$1-$2-$DATE_TIME');")
export PASS=$(php -r "echo hash('sha256', \"$HASH$3\");")
echo
echo ID:        $1
echo EMAIL:     $2
echo PASSWORD:  $3
echo DATE/TIME: $DATE_TIME
echo HASH:      $HASH
echo PASS:      $PASS
echo
