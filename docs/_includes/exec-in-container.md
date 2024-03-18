!!! tip "Executing commands in containers"
    If you're using a container you need to use execute the command *inside* of the container.
    For example, if you're using Docker you must prefix the command with `docker exec` and specify the container name. Here's an example where we assume that the container is `control-center`:

    ```sh
    docker exec -it --user www-data control-center COMMAND [ARGUMENTS...]
    ```
