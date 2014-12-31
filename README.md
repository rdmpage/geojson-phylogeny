# GeoJSON phylogeny

Displaying a phylogeny on a map using GeoJSON, inspired by [GenGIS](http://kiwi.cs.dal.ca/GenGIS/Main_Page).

Below is an example of a set of DNA barcodes, visualised using [Leaflet](http://leafletjs.com/). The source GeoJSON is [here](https://github.com/rdmpage/geojson-phylogeny/blob/master/examples/AMPSA361-13.COI-5P.json).

![Example](https://github.com/rdmpage/geojson-phylogeny/blob/master/examples/AMPSA361-13.COI-5P.png)

Given a NEXUS-format tree file that includes geographic coordinates and information on taxonomic assignment of a set of OTUs, we can generate a GeoJSON file that includes the phylogeny, the location of the OTUs, lines connecting the OTUs to their position in the phylogeny and, if more than one OTU belongs to the same taxon, polygons enclosing the distribution of that taxon.

## References

Barth, W., Mutzel, P., & Jünger, M. (2004). Simple and Efficient Bilayer Cross Counting. J. Graph Algorithms Appl. Journal of Graph Algorithms and Applications. [doi:10.7155/jgaa.00088](http://dx.doi.org/10.7155/jgaa.00088)

Robert G Beiko, Donovan H Parks, Timothy Mankowski, Somayyeh Zangooei, Michael S Porter, David G Armanini, Donald J Baird, et al. (2013). GenGIS 2: Geospatial analysis of traditional and genetic biodiversity, with new gradient algorithms and an extensible plugin framework. PeerJ Inc. [doi:10.7287/peerj.preprints.15](http;//dx.doi.org/10.7287/peerj.preprints.15)

Parks, D. H., Porter, M., Churcher, S., Wang, S., Blouin, C., Whalley, J., Brooks, S., et al. (2009, July 27). GenGIS: A geospatial information system for genomic data. Genome Research. Cold Spring Harbor Laboratory Press. [doi:10.1101/gr.095612.109](http://dx.doi.org/10.1101/gr.095612.109)

Parks, D. H., & Beiko, R. G. (2009). Quantitative visualizations of hierarchically organized data in a geographic context. 2009 17th International Conference on Geoinformatics. [doi:10.1109/geoinformatics.2009.5293552](http://dx.doi.org/10.1109/geoinformatics.2009.5293552)

