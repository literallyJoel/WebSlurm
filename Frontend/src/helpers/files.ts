import { apiEndpoint } from "@/config/config";

export type FileTree = {
  name: string;
  ext: string;
  contents?: FileTree[];
};

export type File = {
  name: string;
  ext: string;
  URL: string;
  contents?: string;
  blob?: Blob;
};

export type DownloadTreeRequest = {
  token: string;
  jobId: string;
};
export type DownloadFileRequest = DownloadTreeRequest & {
  filePath: string;
};

const downloadTree = async (
  type: "input" | "output",
  token: string,
  jobId: string
): Promise<FileTree[]> => {
  const response = await fetch(`${apiEndpoint}/files/${type}/tree/${jobId}`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }

  return await response.json();
};

export const downloadInputTree = async (
  request: DownloadTreeRequest
): Promise<FileTree[]> => {
  return downloadTree("input", request.token, request.jobId);
};

export const downloadOutputTree = async (
  request: DownloadTreeRequest
): Promise<FileTree[]> => {
  return downloadTree("output", request.token, request.jobId);
};

export const textFileExtensions = [
  "txt",
  "json",
  "yaml",
  "yml",
  "xml",
  "csv",
  "md",
  "js",
  "py",
  "html",
  "css",
];

const downloadFile = async (
  type: "input" | "output",
  token: string,
  jobId: string,
  filePath: string
): Promise<File | undefined> => {
  //Having periods in the file path causes issues with PHP.
  filePath = filePath.replace(/\./g, "¬dot¬");
  const _filePath = encodeURIComponent(filePath);
  const res = await fetch(
    `${apiEndpoint}/files/${type}/download/${jobId}/${_filePath}`,
    {
      headers: { Authorization: `Bearer ${token}` },
    }
  );

  if (res.status !== 200) {
    return Promise.reject(new Error(res.statusText));
  }

  const file: File = { name: "", ext: "", URL: "" };

  const contentDisposition = res.headers.get("Content-Disposition");
  if (contentDisposition) {
    const matches = contentDisposition.match(/filename="([^"]+)"/);
    if (matches && matches[1]) {
      file.name = decodeURIComponent(matches[1]);
    }
  }

  //This is a small list for now, but can be expanded later.

  if (
    textFileExtensions.includes(file.ext) ||
    textFileExtensions.includes(file.name.split(".").pop()!)
  ) {
    const text = await res.text();
    console.log(text);
    file.contents = text;
    file.blob = new Blob([text]);
    file.URL = URL.createObjectURL(file.blob);
  } else {
    const blob = await res.blob();
    file.blob = blob;
    file.URL = URL.createObjectURL(blob);
  }

  return file;
};

export const downloadInputFile = async (
  request: DownloadFileRequest
): Promise<File | undefined> => {
  return downloadFile("input", request.token, request.jobId, request.filePath);
};

export const downloadOutputFile = async (
  request: DownloadFileRequest
): Promise<File | undefined> => {
  return downloadFile("output", request.token, request.jobId, request.filePath);
};

export const generateFileId = async (
  token: string
): Promise<{ fileId: string }> => {
  const response = await fetch(`${apiEndpoint}/files/new`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    return Promise.reject(new Error(response.statusText));
  }
  return await response.json();
};
