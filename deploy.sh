# ! /bin/sh
# deploy.sh
#
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
# Run deployment inside the docker container
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
#

ENV=$1
CONTAINER=`grep DOCKER_CONTAINER= <.env | cut -d '=' -f2`

# Check if we have a docker file
if [ -z "$ENV" ]
then
    # Don't allow not specifying environment
    echo "Missing environment argument. Usage: ./deploy.sh <init/dev/prod>"
    exit 0
else
    # Don't allow not specificing container to avoid running in wrong environment
    if [ -z "$CONTAINER" ]
    then
        echo "Missing container name argument. Usage: ./deploy.sh <init/dev/prod>"
        exit 0
    else
        echo "Running deployment into $ENV and '$CONTAINER' container..."
        docker exec -it $CONTAINER bash .docker/deploy.sh $ENV
    fi
fi