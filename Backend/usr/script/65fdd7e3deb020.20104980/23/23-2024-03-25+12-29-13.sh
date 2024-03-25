#!/bin/bash
#SBATCH --job-name='1229'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/23//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016e0cb0fc9
wc -c $file0
echo https://pgb.liv.ac.uk/~sgjvivia/api/jobs/23/markcomplete
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/23/markcomplete