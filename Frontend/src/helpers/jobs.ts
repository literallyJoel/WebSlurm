import { apiEndpoint } from "@/config/config";

export type Job = {
  jobId: number;
  jobComplete: number;
  slurmId: number;
  jobTypeId: number;
  jobCompleteTime: number;
  jobStartTime: number;
  userId: string;
  jobName: string;
  fileId: string;
  jobTypeName: string;
  createdByName: string;
};
export type CreateJobRequest = {
  jobTypeId: number;
  jobName: string;
  parameters: JobParameter[];
  fileId?: string;
  organisationId?: string;
};

export type CreateJobResponse = {
  output: string;
};

export type JobParameter = {
  key: string;
  value: string | number | boolean;
};

export const createJob = async (
  job: CreateJobRequest,
  token: string
): Promise<CreateJobResponse> => {
  return (
    await fetch(`${apiEndpoint}/jobs/`, {
      method: "POST",
      body: JSON.stringify(job),
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};

export const getJobs = async (token: string): Promise<Job[]> => {
  const response = await fetch(`${apiEndpoint}/jobs`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  const jobResponse = await response.json();
  if (Array.isArray(jobResponse)) {
    const jobs = jobResponse.filter((job, index) => {
      let jobIds = jobResponse.map((job) => job.jobId);
      return jobIds.indexOf(job.jobId) === index;
    });
    return jobs;
  }

  return [jobResponse];
};

export const getCompletedJobs = async (
  token: string,
  limit?: number
): Promise<Job[]> => {
  const response = await fetch(
    `${apiEndpoint}/jobs/complete${limit && `?limit=${limit}`}`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  const jobResponse = await response.json();
  if (Array.isArray(jobResponse)) {
    const jobs = jobResponse.filter((job, index) => {
      let jobIds = jobResponse.map((job) => job.jobId);
      return jobIds.indexOf(job.jobId) === index;
    });
    return jobs;
  }

  return [jobResponse];
};

export const getFailedJobs = async (
  token: string,
  limit?: number
): Promise<Job[]> => {
  const response = await fetch(
    `${apiEndpoint}/jobs/failed${limit && `?limit=${limit}`}`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  const jobResponse = await response.json();
  if (Array.isArray(jobResponse)) {
    const jobs = jobResponse.filter((job, index) => {
      let jobIds = jobResponse.map((job) => job.jobId);
      return jobIds.indexOf(job.jobId) === index;
    });
    return jobs;
  }

  return [jobResponse];
};

export const getRunningJobs = async (
  token: string,
  limit?: number
): Promise<Job[]> => {
  const response = await fetch(
    `${apiEndpoint}/jobs/running${limit && `?limit=${limit}`}`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  const jobResponse = await response.json();
  if (Array.isArray(jobResponse)) {
    const jobs = jobResponse.filter((job, index) => {
      let jobIds = jobResponse.map((job) => job.jobId);
      return jobIds.indexOf(job.jobId) === index;
    });
    return jobs;
  }

  return [jobResponse];
};

export const getJob = async (
  jobId: string,
  token: string
): Promise<Job | Job[]> => {
  const response = await fetch(`${apiEndpoint}/jobs/${jobId}`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response.json();
};

export const getParameters = async (
  jobId: string,
  token: string
): Promise<JobParameter[]> => {
  const response = await fetch(`${apiEndpoint}/jobs/${jobId}/parameters`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};
