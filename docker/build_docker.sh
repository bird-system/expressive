#!/usr/bin/env bash

APP_PATH=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
TARGET=${@:${#@}}
FORCE=0

function displayHelp {
    local red=`tput setaf 1`
    local green=`tput setaf 2`
    local reset=`tput sgr0`

    echo "${green}Build docker images for BirdSystem"
    echo "Usage: $0 [-f] [TARGET]"
    echo "${red}base ${reset}birdsystem/app"
    echo "${red}development ${reset}birdsystem/app_development"
    echo "${red}testing ${reset}birdsystem/app_testing"
    echo "${red}production ${reset}birdsystem/docker-service-php"
    echo ""
    echo "${red}-f ${reset}Force building the image even if it already existed"
}

while getopts "f:" opt; do
    case ${opt} in
        f | --force)
            FORCE=1
        ;;
    esac
done


case "${TARGET}" in
    "base")
        echo "Building target => ${TARGET}"
        if [ $(docker images birdsystem/app | wc -l) -le 1 ] || [ ${FORCE} -eq 1 ]; then
            docker build -t birdsystem/app -f ${APP_PATH}/app/Dockerfile \
            ${APP_PATH}/app/
        else
            echo "Target ${TARGET} already existed"
        fi
    ;;
    "development")
        echo "Building target => ${TARGET}"
        if [ $(docker images birdsystem/app_development | wc -l) -le 1 ] || [ ${FORCE} -eq 1 ]; then
            docker build -t birdsystem/app_development -f ${APP_PATH}/app/Dockerfile.development \
            ${APP_PATH}/app/
        else
            echo "Target ${TARGET} already existed"
        fi
    ;;
    "testing")
        echo "Building target => ${TARGET}"
        if [ $(docker images birdsystem/app_testing | wc -l) -le 1 ] || [ ${FORCE} -eq 1 ]; then
            docker build -t birdsystem/app_testing -f ${APP_PATH}/app/Dockerfile.testing \
            ${APP_PATH}/app/
        else
            echo "Target ${TARGET} already existed"
        fi
    ;;
    "production")
        echo "Building target => ${TARGET}"
        if [ $(docker images birdsystem/docker-service-app | wc -l) -le 1 ] || [ ${FORCE} -eq 1 ]; then
            docker build -t birdsystem/docker-service-app -f ${APP_PATH}/app/Dockerfile.production \
            ${APP_PATH}/app/
        else
            echo "Target ${TARGET} already existed"
        fi
    ;;
    "" | "-h" | "--help")
        displayHelp
    ;;
    *)
        echo "Unkown building target ${TARGET}"
    ;;
esac

