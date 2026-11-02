# Contrato SpeedGov para NFS-e Nacional encapsulada (SOAP municipal)

Origem: https://notanacional.speedgov.com.br/web_service_xsds_nota_nacional_.zip
Página do provedor: https://notanacional.speedgov.com.br/
Consulta: 30/10/2026.
SHA-256 do ZIP: `1792a2adb4bd4d035fb9705e54e49ba59a581dba4f74a15540167fb8edff6bc4`.

Os XSDs são os arquivos publicados, sem alteração. A validação de envio usa
`enviar_lote_rps_envio_v1.xsd` e seus imports locais; não acessa a rede.
`tsValor` permite duas casas decimais. `ControleIBSCBS` usa `FinNFSe`,
`IndFinal`, `CIndOp`, `TpOper`, `TpEnteGov` e `IndDest`, com esta capitalização.

Este contrato não é o XML nacional nativo e não possui grupos de tributação
regular, estorno, transferência de crédito, ajuste de competência e monofasia.
Validade estrutural não substitui homologação e regras de negócio do município.
