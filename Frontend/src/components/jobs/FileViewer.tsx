import React, {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
import {
  ChonkyActions,
  ChonkyFileActionData,
  FileArray,
  FileData as _FileData,
  FullFileBrowser,
} from "chonky";
import { v4 as uuidv4 } from "uuid";

import { File, FileTree, textFileExtensions } from "@/helpers/files";
import Noty from "noty";
import { downloadInputFile, downloadOutputFile } from "@/helpers/files";
import { useMutation } from "react-query";
import { Editor } from "@monaco-editor/react";
import { IoCloseCircle } from "react-icons/io5";
import { IoMdDownload } from "react-icons/io";
import Tooltip from "../Tooltip";

type FileData = _FileData & {
  parentId?: string;
  childrenIds?: string[];
  isZip?: boolean;
};

type FileMap = {
  [fileId: string]: FileData;
};

const convertFileTree = (fileTree: FileTree[], parentId?: string): FileMap => {
  let fileMap: FileMap = {};
  fileTree.forEach((file) => {
    const id = uuidv4();
    const isDir = file.contents !== undefined;
    let name;
    if (file.ext !== null && file.ext !== "dir") {
      name = `${file.name.split(".")[0]}.${file.ext}`;
    } else {
      name = file.name;
    }
    fileMap[id] = {
      id,
      name,
      isDir,
      parentId,
    };

    if (isDir && file.contents) {
      const childrenMap = convertFileTree(file.contents, id);
      const filteredChildren = Object.fromEntries(
        Object.entries(childrenMap).filter(
          ([_, value]) => value.parentId === id
        )
      );
      fileMap[id].childrenIds = Object.keys(filteredChildren);
      fileMap[id].childrenCount = fileMap[id].childrenIds?.length;
      fileMap = { ...fileMap, ...childrenMap };
    } else {
      fileMap[id].childrenIds = [];
    }
  });

  return fileMap;
};

const useFileMap = (fileTree: FileTree[]) => {
  const rootFolderId = uuidv4(); // Generate a unique ID for the root folder
  const baseFileMap = useMemo(
    () => convertFileTree(fileTree, rootFolderId),
    [fileTree, rootFolderId]
  ); // Pass the rootFolderId as parentId to convertFileTree

  // Filter out the IDs that have no parent (i.e., they are at the root level)
  const rootChildrenIds = Object.keys(baseFileMap).filter(
    (id) => baseFileMap[id].parentId === rootFolderId
  );

  const [fileMap] = useState<FileMap>({
    ...baseFileMap,
    [rootFolderId]: {
      id: rootFolderId,
      name: "Root",
      isDir: true,
      childrenIds: rootChildrenIds,
    },
  });

  const [currentFolderId, setCurrentFolderId] = useState(rootFolderId);

  const currentFolderIdRef = useRef(currentFolderId);
  useEffect(() => {
    currentFolderIdRef.current = currentFolderId;
  }, [currentFolderId]);

  return {
    fileMap,
    currentFolderId,
    setCurrentFolderId,
  };
};

export const useFiles = (
  fileMap: FileMap,
  currentFolderId: string
): FileArray => {
  return useMemo(() => {
    const currentFolder = fileMap[currentFolderId];
    const childrenIds = currentFolder.childrenIds!;
    const files = childrenIds.map((fileId: string) => fileMap[fileId]);
    return files;
  }, [currentFolderId, fileMap]);
};

export const useFolderChain = (
  fileMap: FileMap,
  currentFolderId: string
): FileArray => {
  return useMemo(() => {
    const currentFolder = fileMap[currentFolderId];

    const folderChain = [currentFolder];

    let parentId = currentFolder.parentId;
    while (parentId) {
      const parentFile = fileMap[parentId];
      if (parentFile) {
        folderChain.unshift(parentFile);
        parentId = parentFile.parentId;
      } else {
        break;
      }
    }
    return folderChain;
  }, [currentFolderId, fileMap]);
};

export const useFileActionHandler = (
  setCurrentFolderId: (folderId: string) => void,
  fileMap: FileMap,
  type: "in" | "out",
  jobId: string,
  token: string,
  setFileContent: React.Dispatch<
    React.SetStateAction<{ name: string; content: string } | undefined>
  >
) => {
  type FileList = {
    [id: string]: File;
  };

  //This allows for viewing files that have been downloaded without needing to redownload
  const [downloadedFiles, setDownloadedFiles] = useState<FileList>({});
  const download = useMutation(
    type === "in" ? downloadInputFile : downloadOutputFile
  );
  return useCallback(
    (data: ChonkyFileActionData) => {
      if (data.id === ChonkyActions.OpenFiles.id) {
        const { targetFile } = data.payload;
        if (targetFile && (targetFile.isDir || targetFile.isZip)) {
          setCurrentFolderId(targetFile.id);
        } else if (
          targetFile &&
          textFileExtensions.includes(targetFile.name.split(".").pop() ?? "")
        ) {
          if (downloadedFiles[targetFile.id]) {
            setFileContent({
              name: targetFile.name,
              content: downloadedFiles[targetFile.id].contents ?? "",
            });
          } else {
            let parentId = fileMap[targetFile.id].parentId;
            let filePath = targetFile.name;
            while (parentId) {
              const parentFile = fileMap[parentId];
              parentId = parentFile.parentId;
              if (parentFile) {
                filePath = `${parentFile.name}/${filePath}`;
              }
            }

            filePath = filePath.split("Root/")[1];
            download.mutate(
              { filePath: filePath, jobId: jobId, token: token },
              {
                onError: () => {
                  const notif = new Noty({
                    text: "An error occured downloading your file. Please try again later.",
                    type: "error",
                  });
                  notif.setTimeout(3000);
                  notif.show();
                },
                onSuccess: (file) => {
                  console.log(file);
                  setDownloadedFiles((prev) => {
                    const _prev = prev;
                    //File is only undefined if a non-200 status code is returned, which'll be picked up by the onError
                    _prev[targetFile.id] = file!;
                    return _prev;
                  });
                  setFileContent({
                    name: targetFile.name,
                    content: file!.contents ?? "",
                  });
                },
              }
            );
          }
        }
      } else if (data.id === ChonkyActions.DownloadFiles.id) {
        const { state } = data;
        const selectedFiles = state.selectedFiles;
        if (selectedFiles.length > 1) {
          const notif = new Noty({
            text: "Only one file can be downloaded at a time",
            type: "error",
          });
          notif.setTimeout(3000);
          notif.setTheme("mint");
          notif.show();
        } else {
          const selectedFile = selectedFiles[0];
          if (downloadedFiles[selectedFile.id]) {
            const a = document.createElement("a");
            a.href = downloadedFiles[selectedFile.id].URL;
            a.download = downloadedFiles[selectedFile.id].name;
            a.click();
            return;
          }

          let parentId = fileMap[selectedFile.id].parentId;
          let filePath = selectedFile.name;
          while (parentId) {
            const parentFile = fileMap[parentId];
            parentId = parentFile.parentId;
            if (parentFile) {
              filePath = `${parentFile.name}/${filePath}`;
            }
          }

          filePath = filePath.split("Root/")[1];
          download.mutate(
            { filePath: filePath, jobId: jobId, token: token },
            {
              onError: () => {
                const notif = new Noty({
                  text: "An error occured downloading your file. Please try again later.",
                  type: "error",
                });
                notif.setTimeout(3000);
                notif.show();
              },
              onSuccess: (file) => {
                setDownloadedFiles((prev) => {
                  const _prev = prev;
                  //File is only undefined if a non-200 status code is returned, which'll be picked up by the onError
                  _prev[selectedFile.id] = file!;
                  return _prev;
                });

                const a = document.createElement("a");
                a.href = file!.URL;
                a.download = file!.name;
                a.click();
              },
            }
          );
        }
      }
    },
    [setCurrentFolderId, fileMap]
  );
};

interface props {
  tree?: FileTree[];
  type: "in" | "out";
  jobId: string;
  token: string;
}

export const FileViewer = React.memo(({ tree, type, jobId, token }: props) => {
  if (!tree) {
    return <div>Loading...</div>;
  }
  console.log(jobId);
  const { fileMap, currentFolderId, setCurrentFolderId } = useFileMap(tree);
  const [fileContent, setFileContent] = useState<
    { name: string; content: string } | undefined
  >();
  const files = useFiles(fileMap, currentFolderId);
  const folderChain = useFolderChain(fileMap, currentFolderId);
  const handleFileAction = useFileActionHandler(
    setCurrentFolderId,
    fileMap,
    type,
    jobId,
    token,
    setFileContent
  );
  const fileActions = useMemo(
    () => [ChonkyActions.OpenFiles, ChonkyActions.DownloadFiles],
    []
  );

  if (!fileContent) {
    return (
      <div style={{ height: 400 }}>
        {/*@ts-ignore*/}
        <FullFileBrowser
          files={files}
          folderChain={folderChain}
          fileActions={fileActions}
          onFileAction={handleFileAction}
        />
      </div>
    );
  } else {
    return (
      <>
        <div className="flex flex-row items-center justify-between p-1">
          <div className="font-bold flex-grow text-center">
            {fileContent.name}
          </div>

          <Tooltip text="Download">
            <IoMdDownload
              className="w-6 h-6 text-uol cursor-pointer hover:text-uol/90"
              onClick={() => {
                const a = document.createElement("a");
                a.href = URL.createObjectURL(
                  new Blob([fileContent.content], {
                    type: "text/plain",
                  })
                );
                a.download = fileContent.name;
                a.click();
              }}
            />
          </Tooltip>

          <Tooltip text="Close Editor">
            <IoCloseCircle
              className="w-6 h-6 text-red-500 cursor-pointer hover:text-red-500/90"
              onClick={() => setFileContent(undefined)}
            />
          </Tooltip>
        </div>
        <Editor value={fileContent.content} height={300} />
      </>
    );
  }
});
