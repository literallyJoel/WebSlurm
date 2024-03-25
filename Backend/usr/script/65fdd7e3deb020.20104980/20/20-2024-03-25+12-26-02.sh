#!/bin/bash
#SBATCH --job-name='sdfcijsdfijsoidfjsodifjsdoifjseofinseofi'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/20//slurmout

file0=/home/sgjvivia/public_html/routes/../usr/in/65fdd7e3deb020.20104980/66016d4d71b7a
wc -c $file0
echo https://pgb.liv.ac.uk/~sgjvivia/api/jobs/20/markcomplete
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/20/markcomplete