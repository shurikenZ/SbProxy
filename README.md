# SbProxy
The Service Broker Proxy takes AJAX requests for iLab Batched Lab Servers and generates SOAP messages for the iLab API.

## Getting started
1.
Open SbProxy.php in the editor and change the constants WSDL and LS_ID. Create a Lab Server with your [Experiment Dispatcher](https://github.com/OnlineLabs4All/dispatcher) instance to generate a Lab Server GUID (LS_ID).
2.
Use both files with your web client. XMLHttpRequests (Ajax) need to be sent to soapCall.php.

## Supported methods
SbProxy supports the following methods from the iLab Shared Architecture (ISA):
* Cancel
* GetExperimentStatus
* GetLabConfiguration
* GetLabStatus
* RetrieveResult
* Submit
