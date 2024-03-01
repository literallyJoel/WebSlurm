import { File, downloadExtracted } from "@/helpers/jobs";
import { AuthContext } from "@/providers/AuthProvider/AuthProvider";
import { FileBrowser, FileList, FileActionHandler } from "chonky";
import { useCallback, useContext } from "react";
interface props {
  file: File;
  jobID: string;
}

export const ZipViewer = ({ file, jobID }: props): JSX.Element => {
  const token = useContext(AuthContext).getToken();
  const handleDownload = useCallback<FileActionHandler>((data) => {
    if (data.action.id === "open_files") {
      const id: number = parseInt(data.payload.targetFile.id.split("file")[1]);

      downloadExtracted(jobID, id, token);
    }
  }, []);

  if (file.zipContents !== undefined) {
    const zipContents = file.zipContents.map((file) => {
      return {
        id: file.fileName,
        name: file.fileName + "." + file.fileExtension,
        ext: file.fileExtension,
      };
    });

    const folderChain = [{ id: "root", name: "Zip Contents", isDir: true }];
    return (
      <FileBrowser
        files={zipContents}
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
