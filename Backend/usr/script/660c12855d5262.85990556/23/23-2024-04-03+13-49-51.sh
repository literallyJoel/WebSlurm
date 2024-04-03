#!/bin/bash
#SBATCH --job-name='DCArr'
#SBATCH --output=/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/out/660c12855d5262.85990556/23//slurmout-%a
#SBATCH --array=0-2


arrayfile1="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/23/660d4faa4d9b8-1-extracted/file${SLURM_ARRAY_TASK_ID}"

arrayfile="/root/coding-projects/WebSlurm-ReWrite/WS2/Backend/routes/../usr/in/660c12855d5262.85990556/23/660d4faa4d9b8-extracted/file${SLURM_ARRAY_TASK_ID}"

diff $arrayfile0 $arrayfile1
curl -X POST http://localhost:8080/api/jobs/23/markcomplete > /dev/null 2>&1