export type JobParameter = {
  key: string;
  value: string | number | boolean;
};

export type JobInput = {
  jobID: number;
  jobName: string;
  parameters: JobParameter[];
  fileID?: string;
};

export type CreateJobResponse = { output: string };

export type FileID = { fileID: string };

export type Job = {
  jobID: number;
  jobComplete: number | undefined;
  slurmID: number;
  jobTypeID: number;
  jobCompleteTime: number | undefined;
  jobStartTime: number;
  userID: string;
  jobName: string;
  jobTypeName?: string;
};
export async function createJob(
  job: JobInput,
  token: string
): Promise<CreateJobResponse> {
  console.log("fileID: ", job.fileID);
  return (
    await fetch("/api/jobs/create", {
      method: "POST",
      body: JSON.stringify(job),
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
}

export async function getJobs(token: string): Promise<Job[]> {
  return (
    await fetch("/api/jobs", {
      headers: { Authorization: `Bearer ${token}` },
    })
  ).json();
}

export const getCompletedJobs = async (
  token: string,
  limit?: number,
  userID?: string
): Promise<Job[]> => {
  return (
    await fetch(`/api/jobs/complete?limit=${limit}&userID=${userID}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};

export const getRunningJobs = async (
  token: string,
  limit?: number,
  userID?: string
): Promise<Job[]> => {
  return (
    await fetch(`/api/jobs/running?limit=${limit}&userID=${userID}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};

export const getFailedJobs = async (
  token: string,
  limit?: number,
  userID?: string
): Promise<Job[]> => {
  return (
    await fetch(`/api/jobs/failed?limit=${limit}&userID=${userID}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};

export const getJob = async (
  jobID: string,
  token: string
): Promise<Job | false> => {
  const res = await fetch(`/api/jobs/${jobID}`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  return res.status === 404 ? false : res.json();
};

export const getParameters = async (
  jobID: string,
  token: string
): Promise<JobParameter[]> => {
  return (
    await fetch(`/api/jobs/${jobID}/parameters`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};
export const getFileID = async (token: string): Promise<FileID> => {
  return (
    await fetch(`/api/jobs/fileid`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};
