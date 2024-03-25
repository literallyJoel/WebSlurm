#!/bin/bash
#SBATCH --job-name='1235'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/25//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016f8fe116f
wc -c $file0
echo https://pgb.liv.ac.uk/~sgjvivia/api/jobs/25/markcomplete
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/25/markcomplete > /dev/null 2>&1