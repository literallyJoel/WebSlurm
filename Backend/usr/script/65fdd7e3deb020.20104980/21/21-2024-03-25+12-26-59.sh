#!/bin/bash
#SBATCH --job-name='dacascsdcdac'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/21//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016d8638ea9
wc -c $file0
echo https://pgb.liv.ac.uk/~sgjvivia/api/jobs/21/markcomplete
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/21/markcomplete