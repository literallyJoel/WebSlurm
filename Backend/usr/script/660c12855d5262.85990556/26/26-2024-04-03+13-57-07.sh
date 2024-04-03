#!/bin/bash
#SBATCH --job-name='DCArr4'
#SBATCH --output=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/out/660c12855d5262.85990556/26//slurmout-%a
#SBATCH --array=0-2


arrayfile1="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/26/660d52179c890-1-extracted/file${SLURM_ARRAY_TASK_ID}"

arrayfile0="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/26/660d52179c890-extracted/file${SLURM_ARRAY_TASK_ID}"

diff $arrayfile0 $arrayfile1
curl -X POST http://localhost:8080/api/jobs/26/markcomplete > /dev/null 2>&1