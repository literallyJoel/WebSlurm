import { apiEndpoint } from "@/config/config";
export type JobParameter = {
  key: string;
  value: string | number | boolean;
};

//If theres loads of files we don't want to download them all into memory, so we just download the metadata
export type FileMetadata = {
  fileName: string;
  fileExtension: string;
};

export type OutputFile =
  | {
      type: "file";
      content: File;
    }
  | {
      type: "meta";
      content: FileMetadata[];
    };

export type File = {
  fileName: string;
  fileURL: string;
  fileExt: string;
  fileContents?: string;
  fileBlob?: Blob;
  meta?: FileMetadata[];
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
    await fetch(apiEndpoint + "/jobs/create", {
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
    await fetch(apiEndpoint + "/jobs", {
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
    await fetch(`${apiEndpoint}/jobs/complete?limit=${limit}&userID=${userID}`, {
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
    await fetch(`${apiEndpoint}/jobs/running?limit=${limit}&userID=${userID}`, {
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
    await fetch(`${apiEndpoint}/jobs/failed?limit=${limit}&userID=${userID}`, {
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
  const res = await fetch(`${apiEndpoint}/jobs/${jobID}`, {
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
    await fetch(`${apiEndpoint}/jobs/${jobID}/parameters`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    })
  ).json();
};
export const getFileID = async (token: string): Promise<FileID> => {
  return (
    await fetch(`${apiEndpoint}/jobs/fileid`, {
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
  const res = await fetch(`${apiEndpoint}/jobs/${jobID}/download/in`, {
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
  } else if (file.fileExt === "zip") {
    const zipInfo = await fetch(`${apiEndpoint}/jobs/${jobID}/zipinfo`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    const zipContents: FileMetadata[] = await zipInfo.json();
    file.meta = zipContents;
  } else {
    const blob = await res.blob();
    file.fileURL = URL.createObjectURL(blob);
  }

  return file;
};

export const downloadOutputFile = async (
  token: string,
  jobID: string
): Promise<OutputFile | undefined> => {
  const res = await fetch(`${apiEndpoint}/jobs/${jobID}/download/out`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (res.status === 200) {
    const file: File = { fileName: "", fileURL: "", fileExt: "" };
    const contentDisposition = res.headers.get("Content-Disposition");
    if (!contentDisposition) {
      const meta = await res.json();
      return { type: "meta", content: meta };
    }

    const contentDispositionArray = contentDisposition?.split(".");
    if (contentDispositionArray) {
      file.fileExt =
        contentDispositionArray[contentDispositionArray.length - 1];
    }

    file.fileName = contentDisposition?.split("filename=")[1] || "";

    if (file.fileExt === "txt") {
      const text = await res.text();
      file.fileContents = text;
      const blob = new Blob([text]);
      file.fileBlob = blob;
      file.fileURL = URL.createObjectURL(blob);
    } else {
      const blob = await res.blob();
      file.fileBlob = blob;
      file.fileURL = URL.createObjectURL(blob);
    }

    return { type: "file", content: file };
  } else {
    return undefined;
  }
};

export const downloadZip = async (
  token: string,
  jobID: string
): Promise<void> => {
  const res = await fetch(`${apiEndpoint}/jobs/${jobID}/download/zip`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (res.status === 200) {
    const contentDisposition = res.headers.get("Content-Disposition");
    const fileName =
      contentDisposition?.split("filename=")[1] || "downloaded-file";
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = fileName;
    link.click();
    URL.revokeObjectURL(url);
  }
};

export const downloadFile = async (
  token: string,
  jobID: string,
  fileName: string
): Promise<void> => {
  const res = await fetch(
    `${apiEndpoint}/jobs/${jobID}/download/out/${fileName}`,
    {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    }
  );

  if (res.status === 200) {
    const contentDisposition = res.headers.get("Content-Disposition");
    const fileName =
      contentDisposition?.split("filename=")[1] || "downloaded-file";
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = fileName;
    link.click();
    URL.revokeObjectURL(url);
  }
};
export const downloadExtracted = async (
  jobID: string,
  file: number,
  token: string
): Promise<void> => {
  const res = await fetch(`${apiEndpoint}/jobs/${jobID}/extracted/${file}`, {
    headers: {
      Authorization: `Bearer ${token}`,
    },
  });

  if (res.status === 200) {
    const contentDisposition = res.headers.get("Content-Disposition");
    const fileName =
      contentDisposition?.split("filename=")[1] || "downloaded-file"; // Default filename if not provided

    const blob = await res.blob();
    const url = URL.createObjectURL(blob);

    // Create a link element
    const link = document.createElement("a");
    link.href = url;
    link.download = fileName; // Set the filename for download

    // Trigger a click event on the link to initiate the download
    link.click();

    // Clean up by revoking the object URL
    URL.revokeObjectURL(url);
  }
};
