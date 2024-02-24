import FileViewer from "react-file-viewer";
import type { File } from "@/helpers/jobs";
import { Textarea } from "@/shadui/ui/textarea";
interface ExtendedViewerProps {
  file: File;
}
//The file viewer library doesn't support certain file types so we need to handle them differently
const ExtendedViewer = ({ file }: ExtendedViewerProps): JSX.Element => {
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
    return <></>;
  } else {
    return <FileViewer fileType={file.fileExt} filePath={file.fileURL} />;
  }
};

export default ExtendedViewer
