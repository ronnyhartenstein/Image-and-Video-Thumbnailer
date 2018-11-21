FROM php:7.2-cli-stretch
LABEL maintainer="rha@gmx.li"

RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive \
    apt-get install -y apt-utils dcraw netpbm ufraw ffmpeg imagemagick unzip git && \
    apt-get clean

#COPY . /project
#VOLUME ["/project", "/mnt/source", "/mnt/target"]
#WORKDIR /project
