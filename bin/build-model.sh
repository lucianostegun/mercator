export PTOOLSPATH=./bin/devtools
export CONFIG_DB_HOSTNAME=192.168.1.3
export CONFIG_DB_USERNAME=postgres
export CONFIG_DB_PASSWORD=unidunite
export CONFIG_DB_DATABASE=dms_lite

export CONFIG_DB_LOG_HOSTNAME=
export CONFIG_DB_LOG_USERNAME=
export CONFIG_DB_LOG_PASSWORD=
export CONFIG_DB_LOG_DATABASE=

export CONFIG_DB_SSLA_HOSTNAME=
export CONFIG_DB_SSLA_USERNAME=
export CONFIG_DB_SSLA_PASSWORD=
export CONFIG_DB_SSLA_DATABASE=

export CONFIG_DB_LOG_SSLA_HOSTNAME=
export CONFIG_DB_LOG_SSLA_USERNAME=
export CONFIG_DB_LOG_SSLA_PASSWORD=
export CONFIG_DB_LOG_SSLA_DATABASE=

./bin/devtools/phalcon.php all-models --force --get-set --models=models/base --extends=ModelBase --mapcolumn
./bin/devtools/phalcon.php all-models --force --get-set --models=models/base --extends=ModelBase --mapcolumn --schema=report

export CONFIG_DB_DATABASE=dms_lite_log
./bin/devtools/phalcon.php all-models --force --get-set --models=models/base --extends=ModelBase --mapcolumn
