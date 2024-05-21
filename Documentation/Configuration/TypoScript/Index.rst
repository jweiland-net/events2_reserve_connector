..  include:: /Includes.rst.txt


..  _typoScript:

==========
TypoScript
==========

In this extension we used TypoScript for overriding the fluid template paths of
events2 and reserve extensions.

Inside our extension we override the e-mail template partial of reserve extension to add event
details. (EXT:/Resources/Private/Reserve/Partials/Mail/ReservationDetails.html)

.. code-block:: typoscript

    plugin.tx_events2 {
      view {
        partialRootPaths {
          10 = EXT:events2_reserve_connector/Resources/Events/Partials/
        }
      }
    }

    plugin.tx_reserve {
      view {
        partialRootPaths {
          10 = EXT:events2_reserve_connector/Resources/Private/Reserve/Partials/
        }
      }
    }
