#!/bin/bash
#SBATCH --job-name='t'
#SBATCH --output=out-'t'
#SBATCH --time=20:00
#SBATCH --ntasks=1
#SBATCH --mem-per-cpu=100
echo "Script started at $(date)"
sleep '500'
echo "Script ended at $(date)"
mv out-'t' ~/out-'t'