#!/bin/bash
#SBATCH --job-name='cot7'
#SBATCH --output=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/33//slurmout

out0=/home/sgjvivia/public_html/routes/../usr/out/65fdd7e3deb020.20104980/33/out0
echo 'gmmmm' > $out0
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/33/markcomplete > /dev/null 2>&1