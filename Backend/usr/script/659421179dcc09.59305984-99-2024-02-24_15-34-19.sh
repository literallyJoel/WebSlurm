#!/bin/bash
#SBATCH --job-name='Test 1'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/659421179dcc09.59305984/99
#SBATCH --time=20:00
#SBATCH --ntasks=1
#SBATCH --mem-per-cpu=100
echo "Script started at $(date)"
sleep '1000'
echo "Script ended at $(date)"



php ../../script/jobComplete.php 99