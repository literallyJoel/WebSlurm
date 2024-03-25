#!/bin/bash
#SBATCH --job-name='dsfsdf'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/19//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016d1cb8f51
wc -c $file0
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/19/markcomplete