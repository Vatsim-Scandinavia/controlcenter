# ! /bin/sh
# deploy.sh
#
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
# Quick access the docker container based on the environment variable
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
#

DOCKER_CONTAINER=`grep DOCKER_CONTAINER= <.env | cut -d '=' -f2`

echo "- - - Now entering into '$DOCKER_CONTAINER' container - - -"
docker exec -it $DOCKER_CONTAINER bash