#!/bin/bash
#SBATCH --job-name='WC'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/187//slurmout

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65f45487d0868
wc -m < $file0
php /root/coding-projects/WebSlurm/Backend/routes/../script/jobComplete.php 187