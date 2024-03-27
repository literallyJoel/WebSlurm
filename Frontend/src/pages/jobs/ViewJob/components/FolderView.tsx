import {
  FileMetadata,
  OutputFile,
  downloadExtracted,
  downloadFile,
} from "@/helpers/jobs";
import { useAuthContext } from "@/providers/AuthProvider/AuthProvider";
import { FileBrowser, FileList, FileActionHandler } from "chonky";
import { useCallback } from "react";
interface props {
  file: OutputFile;
  jobID: string;
}

export const FolderView = ({ file, jobID }: props): JSX.Element => {
  //Grab the user token so we can download files
  const authContext = useAuthContext();
  const token = authContext.getToken();

  //This will download files if the user double clicks
  const handleDownload = useCallback<FileActionHandler>((data) => {
    if (data.action.id === "open_files") {
      const payload = data.payload as any;
      if (file.type === "file") {
        const id: number = parseInt(payload.targetFile.id.split("file")[1]);
        downloadExtracted(jobID, id, token);
      } else {
        downloadFile(token, jobID, payload.targetFile.id);
      }
    }
  }, []);

  //Check if its file metadata or the whole file and assign accordingly
  let meta: FileMetadata[] | undefined;
  if (file.type === "file") {
    meta = file.content.meta;
  } else {
    meta = file.content;
  }

  //Make sure the metadata is present
  if (meta !== undefined) {
    //PUt it into the correct format for the file browser
    const fileTree = meta.map((file) => {
      return {
        id: file.fileName,
        name: file.fileName + "." + file.fileExtension,
        ext: file.fileExtension,
      };
    });

    const folderChain = [{ id: "root", name: "Job Output", isDir: true }];
    return (
      //The FileBrowser comes from a library, and does work fine, but TypeScript gets upset at it.
      //@ts-ignore
      <FileBrowser
        files={fileTree}
        folderChain={folderChain}
        onFileAction={handleDownload}
      >
        <FileList />
      </FileBrowser>
    );
  } else {
    return <></>;
  }
};
