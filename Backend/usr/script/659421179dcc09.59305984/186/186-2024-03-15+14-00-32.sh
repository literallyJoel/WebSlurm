#!/bin/bash
#SBATCH --job-name='SimLoad'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/186//slurmout
#SBATCH --time=20:00
#SBATCH --ntasks=1
#SBATCH --mem-per-cpu=100
echo "Script started at $(date)"
sleep '234'
echo "Script ended at $(date)"



php /root/coding-projects/WebSlurm/Backend/routes/../script/jobComplete.php 186