import FileViewer from "react-file-viewer";
import type { File } from "@/helpers/jobs";
import { Textarea } from "@/shadui/ui/textarea";
import { FileBrowser, FileList } from "chonky";
import { ZipViewer } from "./ZipViewer";

interface ExtendedViewerProps {
  file: File;
  jobID: string;
}

//Different file types require different file viewers, and there's no single libarary that handles everyhing we need
//Abstracting the file viewer out into this extended viewer components allows me to handle specific file types
//in an appopriate way, using a single file viewer component
const ExtendedViewer = ({ file, jobID }: ExtendedViewerProps): JSX.Element => {
  if (file.fileExt === "txt") {
    return (
      <div className="p-2">
        <Textarea
          className="bg-slate-100"
          readOnly
          value={file.fileContents!}
        />
      </div>
    );
  } else if (file.fileExt === "zip") {
    return <ZipViewer file={file} jobID={jobID} />;
  } else {
    return <FileViewer fileType={file.fileExt} filePath={file.fileURL} />;
  }
};

export default ExtendedViewer;
