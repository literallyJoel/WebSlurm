#!/bin/bash
#SBATCH --job-name='sda'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/189//slurmout

file0=/root/coding-projects/WebSlurm/Backend/routes/../usr/in/659421179dcc09.59305984/65f454d8816c0
wc -m < $file0
php /root/coding-projects/WebSlurm/Backend/routes/../script/jobComplete.php 189