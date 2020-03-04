chmod 775 . -R
mkdir cache
mkdir log
mkdir sessions
mkdir web/temp
mkdir web/files
chmod 777 cache -R
chown apache:apache . -R
chown apache:apache cache -R
chmod 777 log -R
chown apache:apache log -R
chmod 777 sessions -R
sudo rm -rf sessions/*
chmod 777 web/temp -R
chmod 777 web/files -R
mkdir storage
chown apache:apache storage
chmod -R 777 storage
chown apache:apache web/temp -R

echo "export MERCATOR_SYSTEM_VERSION=\"1.0.0 alpha\"\
export MERCATOR_API_HOSTNAME=http://api.mercator\
export MERCATOR_DB_HOSTNAME=localhost\
export MERCATOR_DB_USERNAME=postgres\
export MERCATOR_DB_PASSWORD=unidunite\
export MERCATOR_DB_DATABASE=mercator" >> ~/.bash_profile
