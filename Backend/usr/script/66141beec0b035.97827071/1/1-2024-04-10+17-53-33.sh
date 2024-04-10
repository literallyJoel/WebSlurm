#!/bin/bash
#SBATCH --job-name='Test!!!'
#SBATCH --output=/root/coding-projects/WebSlurm/Backend/routes/../usr/out/66141beec0b035.97827071/1//slurmout

echo 'test!!!'
curl -X POST http://localhost:8080/api/jobs/1/markcomplete > /dev/null 2>&1