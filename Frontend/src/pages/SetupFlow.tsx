import { createInitial } from "@/helpers/setup";
import CreateOrganisation from "@/pages/organisations/CreateOrganisation";
import CreateAccount from "@/pages/users/create/CreateAccount";
import Noty from "noty";
import { useEffect, useState } from "react";
import { useMutation } from "react-query";

const SetupFlow = () => {
  const [view, setView] = useState<"User" | "Org">("User");
  const [userInfo, setUserInfo] = useState<{
    name: string;
    email: string;
    password: string;
  }>();
  const [organisationName, setOrganisationName] = useState("");

  const _createInital = useMutation(
    "createInitial",
    () => {
      return createInitial({
        userName: userInfo!.name,
        email: userInfo!.email,
        password: userInfo!.password,
        organisationName: organisationName,
      });
    },
    {
      onError: () => {
        new Noty({
          type: "error",
          text: "Failed to complete setup. No changes have been made. Please try again later.",
          timeout: 2000,
        }).show();
      },
      onSuccess: () => {
        new Noty({
          type: "success",
          text: "Setup completed succesfully. You will be redirected momentarily.",
          timeout: 2000,
        }).show();

        setTimeout(() => {
          window.location.reload();
        }, 2000);
      },
    }
  );

  //The orgName gets set on a button press by the user
  //We useEffect to ensure the state is updated before calling mutate
  useEffect(() => {
    if (organisationName !== "") {
      _createInital.mutate();
    }
  }, [organisationName]);
  if (view === "User") {
    return (
      <CreateAccount isSetup setView={setView} setUserInfo={setUserInfo} />
    );
  } else {
    return (
      <CreateOrganisation isSetup setOrganisationName={setOrganisationName} />
    );
  }
};

export default SetupFlow;
