import FileViewer from "react-file-viewer";
import type { OutputFile } from "@/helpers/jobs";
import { Textarea } from "@/shadui/ui/textarea";
import { FolderView } from "./FolderView";

interface ExtendedViewerProps {
  file: OutputFile;
  jobID: string;
}

//Different file types require different file viewers, and there's no single libarary that handles everyhing we need
//Abstracting the file viewer out into this extended viewer components allows me to handle specific file types
//in an appopriate way, using a single file viewer component
const ExtendedViewer = ({ file, jobID }: ExtendedViewerProps): JSX.Element => {
  if (file.type === "file") {
    if (file.content.fileExt === "txt") {
      return (
        <div className="p-2">
          <Textarea
            className="bg-slate-100"
            readOnly
            value={file.content.fileContents!}
          />
        </div>
      );
    } else if (file.content.fileExt === "zip") {
      return <FolderView file={file} jobID={jobID} />;
    } else {
      return (
        <FileViewer
          fileType={file.content.fileExt}
          filePath={file.content.fileURL}
        />
      );
    }
  } else {
    return <FolderView file={file} jobID={jobID} />;
  }
};

export default ExtendedViewer;
