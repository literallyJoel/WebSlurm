export type JobParameter = {
  key: string;
  value: string | number | boolean;
};

export type File = {
  fileName: string;
  fileURL: string;
  fileExt: string;
  fileContents?: string;
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

export const downloadInputFile = async (
  token: string,
  jobID: string
): Promise<File | undefined> => {
  const res = await fetch(`/api/jobs/${jobID}/download/in`, {
    headers: {
      authorization: `Bearer ${token}`,
    },
  });

  if (res.status !== 200) {
    return undefined;
  }

  const file: File = { fileName: "", fileURL: "", fileExt: "" };
  const contentDisposition = res.headers.get("Content-Disposition");
  const contentDispositionArray = contentDisposition?.split(".");
  if (contentDispositionArray) {
    file.fileExt = contentDispositionArray[contentDispositionArray.length - 1];
  }

  file.fileName = contentDisposition?.split("filename=")[1] || "";

  if (file.fileExt === "txt") {
    const text = await res.text();
    file.fileContents = text;
    file.fileURL = URL.createObjectURL(new Blob([text]));
  } else {
    const blob = await res.blob();
    file.fileURL = URL.createObjectURL(blob);
  }

  return file;
};

export const downloadOutputFile = async (
  token: string,
  jobID: string
): Promise<File | undefined> => {
  const res = await fetch(`/api/jobs/${jobID}/download/out`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (res.status === 200) {
    const file: File = { fileName: "", fileURL: "", fileExt: "" };
    const contentDisposition = res.headers.get("Content-Disposition");
    const contentDispositionArray = contentDisposition?.split(".");
    if (contentDispositionArray) {
      file.fileExt =
        contentDispositionArray[contentDispositionArray.length - 1];
    }

    file.fileName = contentDisposition?.split("filename=")[1] || "";

    if (file.fileExt === "txt") {
      const text = await res.text();
      file.fileContents = text;
      file.fileURL = URL.createObjectURL(new Blob([text]));
    } else {
      const blob = await res.blob();
      file.fileURL = URL.createObjectURL(blob);
    }

    return file;
  } else {
    return undefined;
  }
};
