#!/bin/bash
#SBATCH --job-name='WC1447'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/193//slurmout

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65f45f6c98d01
wc -m < $file0
php /root/coding-projects/WebSlurm/Backend/routes/../script/jobComplete.php 193