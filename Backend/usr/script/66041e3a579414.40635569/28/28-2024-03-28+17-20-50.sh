#!/bin/bash
#SBATCH --job-name='AMT12'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/66041e3a579414.40635569/28//slurmout
#SBATCH --array=0-2


arrayfile1="/root/coding-projects/WebSlurm/Backend/routes/../usr/in/66041e3a579414.40635569/6605a6e55098a-1-extracted/file${SLURM_ARRAY_TASK_ID}"

arrayfile0="/root/coding-projects/WebSlurm/Backend/routes/../usr/in/66041e3a579414.40635569/6605a6e55098a-extracted/file${SLURM_ARRAY_TASK_ID}"

diff $arrayfile0 $arrayfile1
curl -X POST https://pgb.liv.ac.uk/~sgjvivia/api/jobs/28/markcomplete > /dev/null 2>&1