Reserva WP
==========

Plugin para gerenciamento de reservas em WordPress

Atualmente funciona somente em custom post type cujo slug é 'listing'

> Possui um simples calendário de reservas
> Expira posts (publicado para qualquer outro status)
> Dispara emails antes e no dia da expiração do post
> Gerencia posts e seus respectivos pagamentos


Workflow
------------

> Na criação do anúncio, o calendário é mostrado no *front-end* através de um shortcode.
> Ao salvar o anúncio, ele é enviado para revisão. É criado uma transação (cpt) que gerenciará os status do anúncio (cpt). 
> Após aprovado, libera-se link de pagamento pelo PagSeguro na listagem de anúncios (dashboard back-end).
> Para gerar esse link o plugin pega um código único de transação do PagSeguro.
> Todo pagamento é realizado numa lightbox (que funciona separadamente)
> Feito o pagamento, o anúncio automaticamente é publicado e tem um prazo de expiração (variável, mas temporariamente por 1 ano.)
> O plugin envia um email antes da expiração (configurada para 7 dias antes).
> No dia da expiração o plugin muda o status do anuncio (cpt) e envia um email para o cliente.

